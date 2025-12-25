<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SubscriptionCancelledMail extends Mailable
{
    use SerializesModels;

    public function __construct(public $subscription) {}

    public function build()
    {
        return $this
            ->subject('Your subscription has been cancelled')
            ->view('emails.subscription.cancelled');
    }
}
