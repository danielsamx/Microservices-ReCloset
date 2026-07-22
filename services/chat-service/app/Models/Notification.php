<?php
namespace App\Models;
use App\Casts\EncryptedCast;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['user_id','type','title','body','data','read_at'];
    protected $casts = [
        'title' => EncryptedCast::class,           // cifrado AES-256-CBC en reposo
        'body' => EncryptedCast::class,            // cifrado AES-256-CBC en reposo
        'data' => 'encrypted:array',               // cifrado nativo de Laravel para JSON
        'read_at' => 'datetime',
    ];
}
