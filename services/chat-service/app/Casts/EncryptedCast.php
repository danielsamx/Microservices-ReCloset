<?php
namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Cast que cifra el valor al escribir en BD (AES-256-CBC vía APP_KEY)
 * y lo descifra al leer. Maneja valores nulos y datos legacy no cifrados
 * para no romper registros existentes durante la migración.
 */
class EncryptedCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            // Dato legacy no cifrado: devolver tal cual para retrocompatibilidad.
            return $value;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }
        return Crypt::encryptString($value);
    }
}
