<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Support\AuthClient;

class CatalogController extends Controller
{
    private const WITH = ['media', 'category', 'size', 'sizes', 'color'];

    public function __construct(private AuthClient $auth) {}

    public function index(Request $request)
    {
        $q = Item::query()->with(self::WITH)->where('status', Item::STATUS_AVAILABLE);

        if ($token = $request->bearerToken()) {
            if ($user = $this->auth->verify($token)) {
                $q->where('owner_id', '!=', $user['id']);
            }
        }

        if ($request->filled('category_id')) $q->where('category_id', (int) $request->query('category_id'));
        if ($request->filled('color_id')) $q->where('color_id', (int) $request->query('color_id'));
        // el filtro de talla coincide con CUALQUIERA de las tallas de la publicación
        if ($request->filled('size_id')) {
            $sizeId = (int) $request->query('size_id');
            $q->whereHas('sizes', fn ($s) => $s->where('sizes.id', $sizeId));
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
        $item = Item::with(self::WITH)->find($id);
        if (!$item) return response()->json(['message' => 'No encontramos ese recurso.'], 404);
        return response()->json(['item' => $item]);
    }
}
