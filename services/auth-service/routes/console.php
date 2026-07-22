<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

/**
 * Diagnóstico de correo. Envía directo (sin SafeMailer) y muestra el error
 * textual real del proveedor. Uso:
 *   docker compose exec auth-service php artisan mail:test tucorreo@gmail.com
 */
Artisan::command('mail:test {email}', function (string $email) {
    $this->line('Mailer:      ' . config('mail.default'));
    $this->line('From:        ' . config('mail.from.address'));
    $this->line('Resend key:  ' . (!empty(config('services.resend.key')) ? 'presente' : 'VACIA'));
    $this->line('Destino:     ' . $email);
    $this->line('--------------------------------------------------');
    try {
        Mail::raw('Correo de prueba de ReCloset. Si lo recibes, el envio funciona.', function ($m) use ($email) {
            $m->to($email)->subject('Prueba de correo · ReCloset');
        });
        $this->info('OK: el proveedor acepto el envio. Revisa la bandeja (y spam) y el panel de Resend.');
    } catch (\Throwable $e) {
        $this->error('FALLO: ' . get_class($e));
        $this->error($e->getMessage());
    }
})->purpose('Envia un correo de prueba y muestra el error real del proveedor');
