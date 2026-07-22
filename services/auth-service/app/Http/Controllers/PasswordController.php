<?php
namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public const TTL_MINUTES = 60;

    // POST /api/auth/password/forgot  {email}  (público)
    public function forgot(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $email = strtolower($data['email']);
        $user = User::where('email', $email)->first();

        // Respuesta genérica siempre: no revelamos si el correo existe.
        if ($user) {
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => Hash::make($token), 'created_at' => now()],
            );
            $url = config('app.frontend_url').'/#/reset-password?token='.$token.'&email='.urlencode($email);
            Mail::to($email)->send(new ResetPasswordMail($user->name, $url));
            Log::info('auth.password_forgot', ['user_id' => $user->id]);
        }

        return response()->json([
            'message' => 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.',
        ]);
    }

    // POST /api/auth/password/reset  {email, token, password, password_confirmation}  (público)
    public function reset(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);
        $email = strtolower($data['email']);

        $row = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (!$row || !Hash::check($data['token'], $row->token)) {
            return response()->json(['message' => 'El enlace para restablecer no es válido.'], 422);
        }
        if (Carbon::parse($row->created_at)->addMinutes(self::TTL_MINUTES)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return response()->json(['message' => 'El enlace expiró. Solicita uno nuevo.'], 422);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['message' => 'Cuenta no encontrada.'], 404);
        }

        $user->password = $data['password']; // hashed cast
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        // Revocamos todas las sesiones activas por seguridad.
        $user->tokens()->delete();
        Log::info('auth.password_reset', ['user_id' => $user->id]);

        return response()->json(['message' => 'Contraseña actualizada. Ya puedes iniciar sesión.']);
    }
}
