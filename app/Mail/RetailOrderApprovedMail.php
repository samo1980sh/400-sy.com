<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class RetailOrderApprovedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "تم اعتماد طلبك {$this->order->order_no} | Order confirmed",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.retail-order-approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
