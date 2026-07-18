<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
class InternalUserController extends Controller
{
    public function show(Request $request, int $id)
    {
        if ($request->header('X-Internal-Token') !== env('INTERNAL_SERVICE_TOKEN')) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }
        $u = User::find($id);
        if (!$u) return response()->json(['message' => 'No encontramos ese recurso.'], 404);
        return response()->json(['user' => $u->publicProfile()]);
    }
    public function batch(Request $request)
    {
        if ($request->header('X-Internal-Token') !== env('INTERNAL_SERVICE_TOKEN')) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }
        $ids = collect(explode(',', (string) $request->query('ids')))->filter()->map(fn($i)=>(int)$i);
        $users = User::whereIn('id', $ids)->get()->map->publicProfile();
        return response()->json(['users' => $users]);
    }
}
