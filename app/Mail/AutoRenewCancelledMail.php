<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AutoRenewCancelledMail extends Mailable
{
    public function __construct(public $subscription) {}

    public function build()
    {
        return $this
            ->subject('Subscription renewal preference updated')
            ->view('emails.subscription.auto-renew-cancelled');
    }
}

