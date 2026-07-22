<?php
namespace App\Support;

use App\Mail\TwoFactorCodeMail;
use App\Models\TwoFactorChallenge;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TwoFactorService
{
    public const TTL_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;

    /**
     * Crea un reto OTP para el usuario, envía el código por email y
     * devuelve el token público (a usar por el cliente al verificar).
     * Lanza \RuntimeException si el correo no se pudo enviar (el reto se limpia).
     */
    public function start(User $user, string $purpose): string
    {
        // Un único reto vigente por usuario+propósito.
        TwoFactorChallenge::where('user_id', $user->id)->where('purpose', $purpose)->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = Str::random(48);

        $challenge = TwoFactorChallenge::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'token' => $token,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        $context = $purpose === 'enable' ? 'activar la verificación en dos pasos' : 'iniciar sesión';
        $sent = SafeMailer::send($user->email, new TwoFactorCodeMail($code, $context, self::TTL_MINUTES), '2fa');
        if (!$sent) {
            $challenge->delete();
            throw new \RuntimeException('No se pudo enviar el código de verificación.');
        }

        return $token;
    }

    /**
     * Verifica un código contra un reto. Devuelve el User si es válido,
     * o null si el reto no existe / expiró / se agotaron los intentos.
     * Lanza con clave de error legible para el controlador.
     *
     * @return array{ok: bool, user?: User, error?: string}
     */
    public function verify(string $token, string $code, string $purpose): array
    {
        $challenge = TwoFactorChallenge::where('token', $token)->where('purpose', $purpose)->first();

        if (!$challenge || $challenge->isExpired()) {
            $challenge?->delete();
            return ['ok' => false, 'error' => 'El código expiró o no es válido. Solicita uno nuevo.'];
        }

        if ($challenge->attempts >= self::MAX_ATTEMPTS) {
            $challenge->delete();
            return ['ok' => false, 'error' => 'Demasiados intentos fallidos. Solicita un código nuevo.'];
        }

        if (!Hash::check($code, $challenge->code_hash)) {
            $challenge->increment('attempts');
            return ['ok' => false, 'error' => 'Código incorrecto. Verifica e inténtalo de nuevo.'];
        }

        $user = User::find($challenge->user_id);
        $challenge->delete();

        if (!$user) {
            return ['ok' => false, 'error' => 'No pudimos completar la verificación.'];
        }

        return ['ok' => true, 'user' => $user];
    }
}
