<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\Item;
use App\Models\ItemMedia;
use App\Support\MediaClient;

class ItemController extends Controller
{
    private const WITH = ['media', 'category', 'size', 'sizes', 'color'];

    public function __construct(private MediaClient $media) {}

    private function user(Request $r): array { return $r->attributes->get('auth_user'); }

    /** Acepta `size_ids[]` (nuevo) y `size_id` único (compatibilidad). */
    private function resolveSizeIds(array $data): array
    {
        $ids = $data['size_ids'] ?? [];
        if (!$ids && !empty($data['size_id'])) $ids = [$data['size_id']];
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            throw ValidationException::withMessages(['size_ids' => ['Selecciona al menos una talla.']]);
        }
        return $ids;
    }

    public function mine(Request $request)
    {
        $items = Item::with(self::WITH)
            ->where('owner_id', $this->user($request)['id'])
            ->orderByDesc('created_at')->get();
        return response()->json(['items' => $items]);
    }

    public function store(Request $request)
    {
        $user = $this->user($request);
        $data = $request->validate([
            'name' => ['required','string','min:2','max:150'],
            'description' => ['nullable','string','max:2000'],
            'category_id' => ['required','integer','exists:categories,id'],
            'size_ids' => ['array','max:12'],
            'size_ids.*' => ['integer','exists:sizes,id'],
            'size_id' => ['nullable','integer','exists:sizes,id'],
            'color_id' => ['required','integer','exists:colors,id'],
            'price' => ['required','numeric','min:0','max:9999999'],
            'files' => ['required','array','min:1','max:8'],
            'files.*' => ['file','max:51200','mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm'],
        ]);
        $sizeIds = $this->resolveSizeIds($data);

        $item = DB::transaction(function () use ($data, $sizeIds, $user, $request) {
            $item = Item::create([
                'owner_id' => $user['id'],
                'owner_name' => $user['name'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'],
                'size_id' => $sizeIds[0],
                'color_id' => $data['color_id'],
                'price' => $data['price'],
                'status' => Item::STATUS_AVAILABLE,
            ]);
            $item->sizes()->sync($sizeIds);

            $pos = 0;
            foreach ($request->file('files', []) as $file) {
                $descriptor = $this->media->store($file, $item->id);
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

        Log::info('item.created', ['item_id' => $item->id, 'owner' => $user['id'], 'sizes' => count($sizeIds)]);
        return response()->json(['item' => $item->load(self::WITH)], 201);
    }

    public function update(Request $request, int $id)
    {
        $item = Item::find($id);
        if (!$item) return response()->json(['message' => 'No encontramos ese recurso.'], 404);
        if ($item->owner_id !== $this->user($request)['id']) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }
        $data = $request->validate([
            'name' => ['sometimes','string','min:2','max:150'],
            'description' => ['nullable','string','max:2000'],
            'category_id' => ['sometimes','integer','exists:categories,id'],
            'size_ids' => ['sometimes','array','max:12'],
            'size_ids.*' => ['integer','exists:sizes,id'],
            'size_id' => ['sometimes','integer','exists:sizes,id'],
            'color_id' => ['sometimes','integer','exists:colors,id'],
            'price' => ['sometimes','numeric','min:0','max:9999999'],
        ]);

        if ($request->has('size_ids') || $request->has('size_id')) {
            $sizeIds = $this->resolveSizeIds($data);
            $item->sizes()->sync($sizeIds);
            $data['size_id'] = $sizeIds[0];
        }
        unset($data['size_ids']);
        $item->update($data);

        return response()->json(['item' => $item->fresh()->load(self::WITH)]);
    }

    public function changeStatus(Request $request, int $id)
    {
        $item = Item::find($id);
        if (!$item) return response()->json(['message' => 'No encontramos ese recurso.'], 404);
        if ($item->owner_id !== $this->user($request)['id']) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', Item::STATUSES)]]);
        $item->update(['status' => $data['status']]);
        Log::info('item.status_changed', ['item_id' => $item->id, 'status' => $data['status']]);
        return response()->json(['item' => $item->fresh()]);
    }

    public function destroy(Request $request, int $id)
    {
        $item = Item::find($id);
        if (!$item) return response()->json(['message' => 'No encontramos ese recurso.'], 404);
        if ($item->owner_id !== $this->user($request)['id']) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }
        $this->media->deleteByItem($item->id);
        $item->delete();
        Log::info('item.deleted', ['item_id' => $id]);
        return response()->json(['message' => 'Eliminado correctamente.']);
    }

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

    public function internalShow(Request $request, int $id)
    {
        if ($request->header('X-Internal-Token') !== env('INTERNAL_SERVICE_TOKEN')) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }
        $item = Item::with('media')->find($id);
        if (!$item) return response()->json(['message' => 'No encontramos ese recurso.'], 404);
        return response()->json(['item' => [
            'id' => $item->id, 'name' => $item->name,
            'owner_id' => $item->owner_id, 'owner_name' => $item->owner_name,
            'thumb' => optional($item->media->first())->url,
            'status' => $item->status,
        ]]);
    }
}
