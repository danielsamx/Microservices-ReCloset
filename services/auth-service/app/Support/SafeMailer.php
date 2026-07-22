<?php
namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SafeMailer
{
    /**
     * Envía un correo capturando cualquier fallo del proveedor. Nunca lanza
     * excepción (para no romper el registro/login). Registra un diagnóstico
     * claro cuando algo falla, para poder verlo en:
     *   docker compose logs auth-service | grep mail.
     */
    public static function send(string $to, Mailable $mailable, string $context = 'mail'): bool
    {
        $mailer = config('mail.default');
        $from   = config('mail.from.address');
        $keySet = !empty(config('services.resend.key'));

        // Aviso temprano de las dos causas más frecuentes.
        if ($mailer !== 'resend') {
            Log::warning('mail.not_using_resend', [
                'context' => $context,
                'mailer'  => $mailer,
                'hint'    => "MAIL_MAILER no es 'resend' (está en '$mailer'); el correo no se envía por Resend. Ajusta MAIL_MAILER=resend en .env.",
            ]);
        } elseif (!$keySet) {
            Log::warning('mail.missing_api_key', [
                'context' => $context,
                'hint'    => 'RESEND_API_KEY vacío dentro del contenedor. Define RESEND_API_KEY en .env y reinicia auth-service.',
            ]);
        }

        try {
            Mail::to($to)->send($mailable);
            Log::info('mail.sent', ['context' => $context, 'mailer' => $mailer, 'to' => self::mask($to)]);
            return true;
        } catch (\Throwable $e) {
            Log::warning('mail.send_failed', [
                'context'   => $context,
                'mailer'    => $mailer,
                'from'      => $from,
                'key_set'   => $keySet,
                'to'        => self::mask($to),
                'error'     => $e->getMessage(),
                'hint'      => 'Si el error menciona "domain is not verified" o "testing", usa MAIL_FROM_ADDRESS=onboarding@resend.dev y, en modo prueba de Resend, envía solo al correo dueño de la cuenta; o verifica tu dominio en resend.com/domains.',
            ]);
            return false;
        }
    }

    private static function mask(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return '***';
        $u = $parts[0];
        return substr($u, 0, 1) . str_repeat('*', max(1, strlen($u) - 1)) . '@' . $parts[1];
    }
}
