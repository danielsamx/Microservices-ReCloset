<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Mail\WelcomeMail;
use App\Support\SafeMailer;
use App\Support\TwoFactorService;

class AuthController extends Controller
{
    // RF-01 Registration
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);
        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'], // hashed cast
        ]);
        $token = $user->createToken('web')->plainTextToken;
        Log::info('auth.register', ['user_id' => $user->id]);

        // Correos de bienvenida y verificación (no deben romper el registro si fallan).
        SafeMailer::send($user->email, new WelcomeMail($user->name, config('app.frontend_url').'/#/catalog'), 'welcome');
        EmailVerificationController::send($user);

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    // RF-02 Login
    public function login(Request $request, TwoFactorService $twoFactor)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $user = User::where('email', strtolower($data['email']))->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            Log::warning('auth.login_failed', ['email_hash' => sha1(strtolower($data['email']))]);
            throw ValidationException::withMessages(['email' => ['Correo o contraseña incorrectos.']]);
        }

        // Si el usuario tiene 2FA activo, no entregamos token todavía:
        // enviamos un código OTP y devolvemos un reto para completarlo.
        if ($user->two_factor_enabled) {
            try {
                $challenge = $twoFactor->start($user, 'login');
            } catch (\Throwable $e) {
                return response()->json(['message' => 'No pudimos enviar el código de verificación. Inténtalo de nuevo en un momento.'], 502);
            }
            Log::info('auth.login_2fa_challenge', ['user_id' => $user->id]);
            return response()->json([
                'requires_2fa' => true,
                'challenge' => $challenge,
                'message' => 'Te enviamos un código de verificación a tu correo.',
            ]);
        }

        $token = $user->createToken('web')->plainTextToken;
        Log::info('auth.login', ['user_id' => $user->id]);
        return response()->json(['user' => $user, 'token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }

    // Internal introspection endpoint used by other microservices
    public function verify(Request $request)
    {
        $u = $request->user();
        return response()->json(['user' => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]]);
    }
}
