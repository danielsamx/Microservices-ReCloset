<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Notification;
use App\Events\MessageSent;
use App\Events\NotificationCreated;

class MessageController extends Controller
{
    private function user(Request $r): array { return $r->attributes->get('auth_user'); }

    // RF-11: send message -> store -> broadcast (Reverb) -> notify receiver
    public function store(Request $request, int $conversationId)
    {
        $user = $this->user($request);
        $conv = Conversation::find($conversationId);
        if (!$conv) return response()->json(['message' => 'Not found'], 404);
        if (!$conv->isParticipant($user['id'])) return response()->json(['message' => 'Forbidden'], 403);

        $data = $request->validate(['body' => ['required', 'string', 'min:1', 'max:2000']]);

        $message = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $user['id'],
            'sender_name' => $user['name'] ?? null,
            'body' => $data['body'],
        ]);
        $conv->update(['last_message_at' => now()]);

        // 1+2+3: broadcast in real time to the conversation channel
        broadcast(new MessageSent($message));

        // 5: notification for the receiver
        $receiver = $conv->otherParticipant($user['id']);
        $notification = Notification::create([
            'user_id' => $receiver['id'],
            'type' => 'message',
            'title' => 'Nuevo mensaje de ' . ($user['name'] ?? 'un usuario'),
            'body' => \Illuminate\Support\Str::limit($data['body'], 80),
            'data' => ['conversation_id' => $conv->id, 'item_id' => $conv->item_id],
        ]);
        broadcast(new NotificationCreated($notification));

        return response()->json(['message' => $message], 201);
    }
}
