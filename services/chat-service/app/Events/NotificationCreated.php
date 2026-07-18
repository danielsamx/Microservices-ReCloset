<?php
namespace App\Events;
use App\Models\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;
    public function __construct(public Notification $notification) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('notifications.' . $this->notification->user_id)];
    }
    public function broadcastAs(): string { return 'notification.created'; }
    public function broadcastWith(): array
    {
        return ['notification' => [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'title' => $this->notification->title,
            'body' => $this->notification->body,
            'data' => $this->notification->data,
            'read_at' => null,
            'created_at' => $this->notification->created_at->toIso8601String(),
        ]];
    }
}
