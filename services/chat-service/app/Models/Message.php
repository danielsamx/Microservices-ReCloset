<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Message extends Model
{
    protected $fillable = ['conversation_id','sender_id','sender_name','body','read_at'];
    protected $casts = ['read_at' => 'datetime'];
}
