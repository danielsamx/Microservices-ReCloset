<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Support\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function __construct(private TwoFactorService $twoFactor) {}

    // POST /api/auth/2fa/verify  {challenge, code}  (público) — completa el login
    public function verify(Request $request)
    {
        $data = $request->validate([
            'challenge' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $result = $this->twoFactor->verify($data['challenge'], $data['code'], 'login');
        if (!$result['ok']) {
            throw ValidationException::withMessages(['code' => [$result['error']]]);
        }

        $user = $result['user'];
        $token = $user->createToken('web')->plainTextToken;
        Log::info('auth.login_2fa', ['user_id' => $user->id]);

        return response()->json(['user' => $user, 'token' => $token]);
    }

    // POST /api/auth/2fa/enable  (auth) — envía OTP para activar
    public function enable(Request $request)
    {
        $user = $request->user();
        if ($user->two_factor_enabled) {
            return response()->json(['message' => 'La verificación en dos pasos ya está activada.'], 422);
        }
        $challenge = $this->twoFactor->start($user, 'enable');
        return response()->json([
            'message' => 'Te enviamos un código para activar la verificación en dos pasos.',
            'challenge' => $challenge,
        ]);
    }

    // POST /api/auth/2fa/confirm  {challenge, code}  (auth) — activa tras confirmar
    public function confirm(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'challenge' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $result = $this->twoFactor->verify($data['challenge'], $data['code'], 'enable');
        if (!$result['ok'] || $result['user']->id !== $user->id) {
            throw ValidationException::withMessages(['code' => [$result['error'] ?? 'No pudimos confirmar el código.']]);
        }

        $user->two_factor_enabled = true;
        $user->save();
        Log::info('auth.2fa_enabled', ['user_id' => $user->id]);

        return response()->json(['message' => 'Verificación en dos pasos activada.', 'user' => $user->fresh()]);
    }

    // POST /api/auth/2fa/disable  {password}  (auth)
    public function disable(Request $request)
    {
        $user = $request->user();
        $data = $request->validate(['password' => ['required', 'string']]);

        if (!Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => ['Contraseña incorrecta.']]);
        }

        $user->two_factor_enabled = false;
        $user->save();
        Log::info('auth.2fa_disabled', ['user_id' => $user->id]);

        return response()->json(['message' => 'Verificación en dos pasos desactivada.', 'user' => $user->fresh()]);
    }
}
