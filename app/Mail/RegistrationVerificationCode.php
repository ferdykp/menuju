<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code, public int $expiresInMinutes = 10) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kode konfirmasi akun MENUJU');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.registration-code',
            text: 'emails.auth.registration-code-text',
        );
    }
}
