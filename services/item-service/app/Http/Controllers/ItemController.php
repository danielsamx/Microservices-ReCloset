<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Item;
use App\Models\ItemMedia;
use App\Support\MediaClient;

class ItemController extends Controller
{
    public function __construct(private MediaClient $media) {}

    private function user(Request $r): array { return $r->attributes->get('auth_user'); }

    // RF-08: list caller's own items (any status)
    public function mine(Request $request)
    {
        $items = Item::with(['media','category','size','color'])
            ->where('owner_id', $this->user($request)['id'])
            ->orderByDesc('created_at')->get();
        return response()->json(['items' => $items]);
    }

    // RF-04: create item (starts AVAILABLE) with 1..N media files
    public function store(Request $request)
    {
        $user = $this->user($request);
        $data = $request->validate([
            'name' => ['required','string','min:2','max:150'],
            'description' => ['nullable','string','max:2000'],
            'category_id' => ['required','integer','exists:categories,id'],
            'size_id' => ['required','integer','exists:sizes,id'],
            'color_id' => ['required','integer','exists:colors,id'],
            'price' => ['required','numeric','min:0','max:9999999'],
            'files' => ['required','array','min:1','max:8'],
            'files.*' => ['file','max:51200','mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm'],
        ]);

        $item = DB::transaction(function () use ($data, $user, $request) {
            $item = Item::create([
                'owner_id' => $user['id'],
                'owner_name' => $user['name'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'],
                'size_id' => $data['size_id'],
                'color_id' => $data['color_id'],
                'price' => $data['price'],
                'status' => Item::STATUS_AVAILABLE,
            ]);
            $pos = 0;
            foreach ($request->file('files', []) as $file) {
                $descriptor = $this->media->store($file, $item->id);   // -> Media Service
                if ($descriptor) {
                    ItemMedia::create([
                        'item_id' => $item->id,
                        'media_id' => $descriptor['id'],
                        'url' => $descriptor['url'],
                        'mime' => $descriptor['mime'] ?? null,
                        'position' => $pos++,
                    ]);
                }
            }
            return $item;
        });

        Log::info('item.created', ['item_id' => $item->id, 'owner' => $user['id']]);
        return response()->json(['item' => $item->load(['media','category','size','color'])], 201);
    }

    // RF-03/RF-08: only owner can edit
    public function update(Request $request, int $id)
    {
        $item = Item::find($id);
        if (!$item) return response()->json(['message' => 'Not found'], 404);
        if ($item->owner_id !== $this->user($request)['id']) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $data = $request->validate([
            'name' => ['sometimes','string','min:2','max:150'],
            'description' => ['nullable','string','max:2000'],
            'category_id' => ['sometimes','integer','exists:categories,id'],
            'size_id' => ['sometimes','integer','exists:sizes,id'],
            'color_id' => ['sometimes','integer','exists:colors,id'],
            'price' => ['sometimes','numeric','min:0','max:9999999'],
        ]);
        $item->update($data);
        return response()->json(['item' => $item->fresh()->load(['media','category','size','color'])]);
    }

    // RF-03: only owner can change state (available/reserved/sold)
    public function changeStatus(Request $request, int $id)
    {
        $item = Item::find($id);
        if (!$item) return response()->json(['message' => 'Not found'], 404);
        if ($item->owner_id !== $this->user($request)['id']) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', Item::STATUSES)]]);
        $item->update(['status' => $data['status']]);
        Log::info('item.status_changed', ['item_id' => $item->id, 'status' => $data['status']]);
        return response()->json(['item' => $item->fresh()]);
    }

    // RF-08: only owner can delete; media removed via Media Service
    public function destroy(Request $request, int $id)
    {
        $item = Item::find($id);
        if (!$item) return response()->json(['message' => 'Not found'], 404);
        if ($item->owner_id !== $this->user($request)['id']) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $this->media->deleteByItem($item->id);     // -> Media Service deletes blobs
        $item->delete();                            // cascades item_media refs
        Log::info('item.deleted', ['item_id' => $id]);
        return response()->json(['message' => 'Deleted']);
    }

    // RF-09: wardrobe summary
    public function wardrobe(Request $request)
    {
        $ownerId = $this->user($request)['id'];
        $base = Item::where('owner_id', $ownerId);
        return response()->json([
            'total' => (clone $base)->count(),
            'available' => (clone $base)->where('status', Item::STATUS_AVAILABLE)->count(),
            'reserved' => (clone $base)->where('status', Item::STATUS_RESERVED)->count(),
            'sold' => (clone $base)->where('status', Item::STATUS_SOLD)->count(),
        ]);
    }

    // Internal: chat service resolves item owner & title for a conversation
    public function internalShow(Request $request, int $id)
    {
        if ($request->header('X-Internal-Token') !== env('INTERNAL_SERVICE_TOKEN')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $item = Item::with('media')->find($id);
        if (!$item) return response()->json(['message' => 'Not found'], 404);
        return response()->json(['item' => [
            'id' => $item->id, 'name' => $item->name,
            'owner_id' => $item->owner_id, 'owner_name' => $item->owner_name,
            'thumb' => optional($item->media->first())->url,
            'status' => $item->status,
        ]]);
    }
}
