<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'two_factor_enabled'];
    protected $hidden = ['password', 'remember_token'];

    // Exponemos un booleano cómodo para el frontend, sin filtrar la fecha exacta.
    protected $appends = ['email_verified'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
        ];
    }

    protected function emailVerified(): Attribute
    {
        return Attribute::make(get: fn () => !is_null($this->email_verified_at));
    }

    public function publicProfile(): array
    {
        return ['id' => $this->id, 'name' => $this->name];
    }
}
