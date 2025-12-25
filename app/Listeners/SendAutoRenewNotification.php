<?php

namespace App\Listeners;

use App\Events\SubscriptionAutoRenewChanged;
use App\Mail\AutoRenewCancelledMail;
use App\Mail\AutoRenewEnabledMail;
use Illuminate\Support\Facades\Mail;

class SendAutoRenewNotification
{
    public function handle(SubscriptionAutoRenewChanged $event): void
    {
        $user = $event->subscription->user;

        if (! $user || ! $user->email) {
            return;
        }

        if ($event->newState === false) {
            Mail::to($user->email)->send(
                new AutoRenewCancelledMail($event->subscription)
            );
        } else {
            Mail::to($user->email)->send(
                new AutoRenewEnabledMail($event->subscription)
            );
        }
    }
}
