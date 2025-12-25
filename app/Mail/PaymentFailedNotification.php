<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $subscription;
    public $gracePeriodUntil;

    /**
     * Create a new message instance.
     */
    public function __construct(Subscription $subscription, $gracePeriodUntil)
    {
        $this->subscription = $subscription;
        $this->gracePeriodUntil = $gracePeriodUntil;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Failed - Action Required',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-failed',
            with: [
                'userName' => $this->subscription->user->full_name,
                'planName' => strtoupper($this->subscription->plan_id),
                'gracePeriodUntil' => $this->gracePeriodUntil->format('F d, Y'),
                'daysRemaining' => now()->diffInDays($this->gracePeriodUntil),
                'dashboardUrl' => config('app.frontend_url') . '/dashboard',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
