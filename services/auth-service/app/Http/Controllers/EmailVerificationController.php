<?php
namespace App\Http\Controllers;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailVerificationController extends Controller
{
    public const TTL_MINUTES = 60;

    /**
     * Genera un token de verificación, lo guarda hasheado y envía el correo.
     * Reutilizado por el registro y por el reenvío.
     */
    public static function send(User $user): void
    {
        if ($user->email_verified) {
            return;
        }

        $token = Str::random(64);
        DB::table('email_verification_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($token), 'created_at' => now()],
        );

        $url = config('app.frontend_url').'/#/verify-email?token='.$token.'&email='.urlencode($user->email);
        Mail::to($user->email)->send(new VerifyEmailMail($user->name, $url));
    }

    // POST /api/auth/email/resend  (auth)
    public function resend(Request $request)
    {
        $user = $request->user();
        if ($user->email_verified) {
            return response()->json(['message' => 'Tu correo ya está verificado.']);
        }
        self::send($user);
        Log::info('auth.email_verification_resent', ['user_id' => $user->id]);
        return response()->json(['message' => 'Te enviamos un nuevo enlace de verificación.']);
    }

    // POST /api/auth/email/verify  {email, token}  (público)
    public function verify(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
        ]);
        $email = strtolower($data['email']);

        $row = DB::table('email_verification_tokens')->where('email', $email)->first();
        if (!$row || !Hash::check($data['token'], $row->token)) {
            return response()->json(['message' => 'El enlace de verificación no es válido.'], 422);
        }
        if (Carbon::parse($row->created_at)->addMinutes(self::TTL_MINUTES)->isPast()) {
            DB::table('email_verification_tokens')->where('email', $email)->delete();
            return response()->json(['message' => 'El enlace de verificación expiró. Solicita uno nuevo.'], 422);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['message' => 'Cuenta no encontrada.'], 404);
        }
        if (!$user->email_verified) {
            $user->email_verified_at = now();
            $user->save();
        }
        DB::table('email_verification_tokens')->where('email', $email)->delete();
        Log::info('auth.email_verified', ['user_id' => $user->id]);

        return response()->json(['message' => 'Correo verificado correctamente.', 'user' => $user]);
    }
}
