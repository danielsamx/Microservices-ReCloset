<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $context = 'iniciar sesión',
        public int $minutes = 10,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu código de acceso · ReCloset');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp', with: [
            'code' => $this->code,
            'context' => $this->context,
            'minutes' => $this->minutes,
        ]);
    }
}
