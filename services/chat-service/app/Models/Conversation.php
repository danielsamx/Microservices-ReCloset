<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Conversation extends Model
{
    protected $fillable = [
        'item_id','item_title','item_thumb',
        'owner_id','owner_name','interested_id','interested_name','last_message_at',
    ];
    protected $casts = ['last_message_at' => 'datetime'];
    public function messages(): HasMany { return $this->hasMany(Message::class); }
    public function isParticipant(int $userId): bool
    {
        return $this->owner_id === $userId || $this->interested_id === $userId;
    }
    public function otherParticipant(int $userId): array
    {
        return $userId === $this->owner_id
            ? ['id' => $this->interested_id, 'name' => $this->interested_name]
            : ['id' => $this->owner_id, 'name' => $this->owner_name];
    }
}
