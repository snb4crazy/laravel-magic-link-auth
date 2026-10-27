<?php

namespace Snb4crazy\LaravelMagicLinkAuth\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MagicLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $magicLinkUrl,
        public readonly string $appName,
        public readonly int $expiresInMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your '.$this->appName.' magic login link',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'magic-link-auth::emails.magic-link',
            with: [
                'magicLinkUrl' => $this->magicLinkUrl,
                'appName' => $this->appName,
                'expiresInMinutes' => $this->expiresInMinutes,
            ],
        );
    }
}
