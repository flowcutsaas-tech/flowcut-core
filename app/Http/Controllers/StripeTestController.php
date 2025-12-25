<?php

namespace App\Http\Controllers;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Checkout\Session as CheckoutSession;

class StripeTestController extends Controller
{
    public function testCheckout()
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $customer = Customer::create([
            'email' => 'test@example.com',
        ]);

        $session = CheckoutSession::create([
            'mode' => 'subscription',
            'customer' => $customer->id,
            'line_items' => [[
                'price' => config('services.stripe.plans.basic'),

                'quantity' => 1,
                'tax_rates' => ['txr_1SgNVqIVBi4OZIkydmW8F0kM'], // ضع Tax Rate ID الحقيقي
            ]],
            'success_url' => url('/success'),
            'cancel_url' => url('/cancel'),
        ]);

        return redirect($session->url);
    }
}
