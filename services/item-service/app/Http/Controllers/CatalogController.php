<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Support\AuthClient;

class CatalogController extends Controller
{
    public function __construct(private AuthClient $auth) {}

    // RF-06 / RF-07: public catalog, only AVAILABLE items from OTHER users,
    // combined filters by category + size + color.
    public function index(Request $request)
    {
        $q = Item::query()->with(['media','category','size','color'])
            ->where('status', Item::STATUS_AVAILABLE);

        // Exclude the caller's own items when a valid token is present (optional auth).
        if ($token = $request->bearerToken()) {
            if ($user = $this->auth->verify($token)) {
                $q->where('owner_id', '!=', $user['id']);
            }
        }

        foreach (['category_id','size_id','color_id'] as $f) {
            if ($request->filled($f)) $q->where($f, (int) $request->query($f));
        }
        if ($request->filled('search')) {
            $s = $request->query('search');
            $q->where(fn($w) => $w->where('name','ilike',"%$s%")->orWhere('description','ilike',"%$s%"));
        }

        $items = $q->orderByDesc('created_at')->paginate(min((int)$request->query('per_page', 12), 48));
        return response()->json($items);
    }

    public function show(int $id)
    {
        $item = Item::with(['media','category','size','color'])->find($id);
        if (!$item) return response()->json(['message' => 'Not found'], 404);
        return response()->json(['item' => $item]);
    }
}
