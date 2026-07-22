<?php
namespace App\Models;
use App\Casts\EncryptedCast;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['conversation_id','sender_id','sender_name','body','read_at'];
    protected $casts = [
        'body' => EncryptedCast::class,    // cifrado AES-256-CBC en reposo
        'read_at' => 'datetime',
    ];
}
