<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Conversation;
use App\Models\Message;
use App\Support\ItemClient;

class ConversationController extends Controller
{
    public function __construct(private ItemClient $items) {}
    private function user(Request $r): array { return $r->attributes->get('auth_user'); }

    // RF-12: list conversations, most recent first, with related item + unread count
    public function index(Request $request)
    {
        $uid = $this->user($request)['id'];
        $rows = Conversation::where('owner_id', $uid)->orWhere('interested_id', $uid)
            ->orderByDesc('last_message_at')->orderByDesc('created_at')->get();

        $data = $rows->map(function (Conversation $c) use ($uid) {
            $last = $c->messages()->latest('id')->first();
            $unread = $c->messages()->whereNull('read_at')->where('sender_id', '!=', $uid)->count();
            return [
                'id' => $c->id,
                'item' => ['id' => $c->item_id, 'title' => $c->item_title, 'thumb' => $c->item_thumb],
                'other' => $c->otherParticipant($uid),
                'last_message' => $last?->body,
                'last_message_at' => optional($c->last_message_at)->toIso8601String(),
                'unread' => $unread,
            ];
        });
        return response()->json(['conversations' => $data]);
    }

    // RF-10: start a conversation about a specific item (no self-chat)
    public function store(Request $request)
    {
        $user = $this->user($request);
        $data = $request->validate(['item_id' => ['required', 'integer']]);

        $item = $this->items->find($data['item_id']);
        if (!$item) return response()->json(['message' => 'Esa publicación ya no existe.'], 404);

        if ((int) $item['owner_id'] === (int) $user['id']) {
            return response()->json(['message' => 'No puedes iniciar una conversación contigo mismo.'], 422);
        }

        $conv = Conversation::firstOrCreate(
            ['item_id' => $item['id'], 'interested_id' => $user['id']],
            [
                'item_title' => $item['name'],
                'item_thumb' => $item['thumb'] ?? null,
                'owner_id' => $item['owner_id'],
                'owner_name' => $item['owner_name'] ?? null,
                'interested_name' => $user['name'] ?? null,
                'last_message_at' => now(),
            ]
        );
        Log::info('chat.conversation_started', ['conversation_id' => $conv->id, 'item' => $item['id']]);
        return response()->json(['conversation' => $conv], 201);
    }

    // messages of a conversation (participant only). Marks incoming as read.
    public function show(Request $request, int $id)
    {
        $uid = $this->user($request)['id'];
        $conv = Conversation::find($id);
        if (!$conv) return response()->json(['message' => 'No encontramos ese recurso.'], 404);
        if (!$conv->isParticipant($uid)) return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);

        $conv->messages()->whereNull('read_at')->where('sender_id', '!=', $uid)->update(['read_at' => now()]);
        return response()->json([
            'conversation' => [
                'id' => $conv->id,
                'item' => ['id' => $conv->item_id, 'title' => $conv->item_title, 'thumb' => $conv->item_thumb],
                'other' => $conv->otherParticipant($uid),
            ],
            'messages' => $conv->messages()->orderBy('id')->get(),
        ]);
    }

    // RF-13: delete a conversation and its messages
    public function destroy(Request $request, int $id)
    {
        $uid = $this->user($request)['id'];
        $conv = Conversation::find($id);
        if (!$conv) return response()->json(['message' => 'No encontramos ese recurso.'], 404);
        if (!$conv->isParticipant($uid)) return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        $conv->delete(); // cascades messages
        return response()->json(['message' => 'Eliminado correctamente.']);
    }
}
