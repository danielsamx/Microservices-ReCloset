<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TwoFactorChallenge extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // solo created_at, gestionado por la BD/insert

    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'attempts' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $c) {
            if (empty($c->id)) {
                $c->id = (string) Str::uuid();
            }
            if (empty($c->created_at)) {
                $c->created_at = now();
            }
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
