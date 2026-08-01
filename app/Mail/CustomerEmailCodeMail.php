<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerEmailCodeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $purpose,
        public readonly int $expiresMinutes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('customer_auth.email_subject_' . $this->purpose),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-email-code',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
