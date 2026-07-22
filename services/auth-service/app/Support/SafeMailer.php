<?php
namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SafeMailer
{
    /**
     * Envía un correo capturando cualquier fallo del proveedor (p. ej. Resend
     * en modo prueba rechaza destinatarios que no son el dueño de la cuenta).
     * Devuelve true si se envió, false si falló. Nunca lanza excepción.
     */
    public static function send(string $to, Mailable $mailable, string $context = 'mail'): bool
    {
        try {
            Mail::to($to)->send($mailable);
            return true;
        } catch (\Throwable $e) {
            Log::warning('mail.send_failed', ['context' => $context, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
