<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Notification;
class NotificationController extends Controller
{
    private function uid(Request $r): int { return $r->attributes->get('auth_user')['id']; }

    public function index(Request $request)
    {
        $n = Notification::where('user_id', $this->uid($request))
            ->orderByDesc('id')->limit(100)->get();
        return response()->json(['notifications' => $n]);
    }
    public function unreadCount(Request $request)
    {
        return response()->json([
            'count' => Notification::where('user_id', $this->uid($request))->whereNull('read_at')->count(),
        ]);
    }
    public function markRead(Request $request, int $id)
    {
        Notification::where('user_id', $this->uid($request))->where('id', $id)->update(['read_at' => now()]);
        return response()->json(['message' => 'ok']);
    }
    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $this->uid($request))->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['message' => 'ok']);
    }
}
