<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Conversation;

/**
 * Authorizes Laravel Echo private-channel subscriptions. Because auth is handled
 * via token introspection (not a local users table), we sign the Pusher-protocol
 * handshake manually after checking channel membership.
 */
class BroadcastAuthController extends Controller
{
    public function authenticate(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        $channel = (string) $request->input('channel_name');
        $socketId = (string) $request->input('socket_id');

        if (!$this->authorized($user['id'], $channel)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $secret = (string) env('REVERB_APP_SECRET');
        $key = (string) env('REVERB_APP_KEY');
        $signature = hash_hmac('sha256', $socketId . ':' . $channel, $secret);
        return response()->json(['auth' => $key . ':' . $signature]);
    }

    private function authorized(int $uid, string $channel): bool
    {
        // private-conversation.{id}
        if (preg_match('/^private-conversation\.(\d+)$/', $channel, $m)) {
            $conv = Conversation::find((int) $m[1]);
            return $conv && $conv->isParticipant($uid);
        }
        // private-notifications.{userId}
        if (preg_match('/^private-notifications\.(\d+)$/', $channel, $m)) {
            return (int) $m[1] === $uid;
        }
        return false;
    }
}
