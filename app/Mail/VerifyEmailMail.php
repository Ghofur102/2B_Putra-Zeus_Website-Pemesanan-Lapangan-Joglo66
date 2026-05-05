<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifikasi Email Anda - Joglo66',
        );
    }

    public function content(): Content
    {
        $verifyUrl = route('verification.verify', ['token' => $this->token], true);

        return new Content(
            view: 'emails.verify-email',
            with: [
                'user' => $this->user,
                'verifyUrl' => $verifyUrl,
                'expiresAt' => now()->addHours(24)->format('d M Y H:i'),
            ],
        );
    }
}
