<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionSuspendedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $subscription;
    public $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(Subscription $subscription, string $reason)
    {
        $this->subscription = $subscription;
        $this->reason = $reason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Subscription Has Been Suspended',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-suspended',
            with: [
                'userName' => $this->subscription->user->full_name,
                'planName' => strtoupper($this->subscription->plan_id),
                'reason' => $this->reason,
                'dashboardUrl' => config('app.frontend_url') . '/dashboard',
                'supportEmail' => config('mail.from.address'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
