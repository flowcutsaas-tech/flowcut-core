<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AutoRenewEnabledMail extends Mailable
{
    use SerializesModels;

    public function __construct(public $subscription) {}

    public function build()
    {
        return $this
            ->subject('Subscription renewal preference updated')
            ->view('emails.subscription.auto-renew-enabled');
    }
}
