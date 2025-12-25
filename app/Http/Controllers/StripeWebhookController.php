<?php
namespace App\Http\Controllers;

use App\Jobs\CreateTenantJob;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\PaymentFailureService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    protected $subscriptionService;
    protected $paymentFailureService;

    public function __construct(
        SubscriptionService $subscriptionService,
        PaymentFailureService $paymentFailureService
    ) {
        $this->subscriptionService   = $subscriptionService;
        $this->paymentFailureService = $paymentFailureService;
    }

    /**
     * Handle Stripe webhooks.
     */
    public function handleWebhook(Request $request)
    {
        $payload        = $request->getContent();
        $sigHeader      = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe Webhook Signature Verification Failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe Webhook Invalid Payload: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->handleCheckoutSessionCompleted($session);
                break;

            case 'charge.succeeded':
                $charge = $event->data->object;
                $this->handleChargeSucceeded($charge);
                break;
            case 'invoice.paid':
            case 'invoice.payment_succeeded':
                $invoice = $event->data->object;
                $this->handleInvoicePaymentSucceeded($invoice);
                break;

            case 'invoice.payment_failed':
                $invoice = $event->data->object;
                $this->handleInvoicePaymentFailed($invoice);
                break;
            case 'customer.subscription.deleted':
                $subscriptionObject = $event->data->object;
                $this->handleSubscriptionDeleted($subscriptionObject);
                break;
            case 'checkout.session.expired':
                Log::info('Checkout expired', [
                    'session_id' => $event->data->object->id,
                    'customer'   => $event->data->object->customer,
                    'email'      => $event->data->object->customer_details->email ?? null,
                ]);

                // OPTIONAL:
                // Queue::push(SendAbandonedCheckoutEmailJob::class);

                break;
            case 'customer.subscription.updated':

                $stripeSubscription = $event->data->object;

                $subscription = Subscription::where(
                    'stripe_subscription_id',
                    $stripeSubscription->id
                )->first();

                if (! $subscription) {
                    break;
                }

                $oldAutoRenew = (bool) $subscription->auto_renew;
                $newAutoRenew = ! $stripeSubscription->cancel_at_period_end;

                // تحديث القاعدة
                $subscription->update([
                    'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end,
                    'auto_renew'           => $newAutoRenew,
                    'current_period_end'   => $stripeSubscription->current_period_end, // ⭐
                ]);

                // 🔑 هذا هو الشرط الصحيح الوحيد
                if ($oldAutoRenew !== $newAutoRenew) {

                    $user = $subscription->user;

                    if ($user && $user->email) {

                        if ($newAutoRenew === false) {
                            \Mail::to($user->email)->send(
                                new \App\Mail\AutoRenewCancelledMail($subscription)
                            );
                        } else {
                            \Mail::to($user->email)->send(
                                new \App\Mail\AutoRenewEnabledMail($subscription)
                            );
                        }
                    }
                }

                Log::info('Auto-renew change processed', [
                    'subscription_id' => $subscription->id,
                    'old_auto_renew'  => $oldAutoRenew,
                    'new_auto_renew'  => $newAutoRenew,
                ]);

                break;

            default:
                Log::debug('Received Stripe event type: ' . $event->type);
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Handle checkout.session.completed event.
     * This is triggered when payment is successfully completed.
     */
    protected function handleCheckoutSessionCompleted($session): void
    {
        $stripeSubscriptionId = $session->subscription ?? null;
        $metadata             = $session->metadata ?? null;

        if (! $stripeSubscriptionId || ! $metadata || empty($metadata->user_id)) {
            Log::error('Stripe Webhook: Missing required data', [
                'subscription' => $stripeSubscriptionId,
                'metadata'     => (array) $metadata,
            ]);
            return;
        }

        // 🛑🛑🛑 هذا هو القفل — لا شيء غيره
        $alreadyExists = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->exists();
        if ($alreadyExists) {
            Log::warning('Stripe webhook replay ignored', [
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);
            return;
        }

        // ⬇️ من هنا فصاعدًا يُنفذ مرة واحدة فقط
        $months = (int) ($metadata->term ?? 12);

        $subscription = Subscription::create([
            'user_id'                 => $metadata->user_id,
            'plan_id'                 => $metadata->plan,
            'price'                   => ($session->amount_total / 100) / $months,
            'status'                  => 'active',

            'stripe_subscription_id'  => $stripeSubscriptionId,
            'stripe_customer_id'      => $session->customer,

            'starts_at'               => now(),
            'ends_at'                 => now()->addMonths($months),

            'payment_retry_count'     => 0,
            'failed_payment_attempts' => 0,
        ]);
        // ✅ ربط الاشتراك بالـ Tenant الموجود (إن وجد)
        $tenant = \App\Models\Tenant::where('user_id', $metadata->user_id)->first();

        if ($tenant) {
            $subscription->update([
                'tenant_id' => $tenant->id,
            ]);
        }

        Payment::create([
            'subscription_id' => $subscription->id,
            'coupon_id'       => null,
            'amount'          => $session->amount_total / 100,
            'discount'        => 0,
            'total'           => $session->amount_total / 100,
            'payment_method'  => 'stripe_checkout',
            'transaction_id'  => $session->payment_intent,
            'status'          => 'completed',
        ]);

        // 🛑 قفل نهائي: إذا كان هناك Tenant مرتبط بهذا المستخدم سابقًا
        $hasTenant = \App\Models\Tenant::where('user_id', $metadata->user_id)->exists();

        if (! $hasTenant) {
            CreateTenantJob::dispatch(
                $subscription->id,
                $stripeSubscriptionId
            );
        }

        Log::info('Stripe checkout completed successfully', [
            'subscription_id'        => $subscription->id,
            'stripe_subscription_id' => $stripeSubscriptionId,
        ]);
    }

    /**
     * Handle charge.succeeded event.
     * This is the actual payment charge that succeeded.
     * Record this as a payment in the database.
     */
    protected function handleChargeSucceeded($charge): void
    {
        // Get invoice ID from charge
        $invoiceId = $charge->invoice ?? null;

        if (! $invoiceId) {
            Log::debug('Charge succeeded but no invoice associated', ['charge_id' => $charge->id]);
            return;
        }

        // Find subscription by invoice
        $subscription = Subscription::whereHas('payments', function ($query) use ($invoiceId) {
            $query->where('transaction_id', $invoiceId);
        })->first();

        // If not found by invoice, try to find by stripe subscription
        if (! $subscription && $charge->subscription) {
            $subscription = Subscription::where('stripe_subscription_id', $charge->subscription)->first();
        }

        if (! $subscription) {
            Log::debug('Subscription not found for charge', ['charge_id' => $charge->id, 'invoice_id' => $invoiceId]);
            return;
        }

        // Check if payment already recorded
        $existingPayment = Payment::where('transaction_id', $charge->id)->first();
        if ($existingPayment) {
            Log::debug('Payment already recorded', ['charge_id' => $charge->id]);
            return;
        }

        // Create payment record
        Payment::create([
            'subscription_id' => $subscription->id,
            'coupon_id'       => null,
            'amount'          => $charge->amount / 100, // Stripe uses cents
            'discount'        => 0,
            'total'           => $charge->amount / 100,
            'payment_method'  => $charge->payment_method_details->type ?? 'card',
            'transaction_id'  => $charge->id,
            'status'          => 'completed',
        ]);

        Log::info("Payment recorded for charge: {$charge->id}, subscription: {$subscription->id}");
    }

    /**
     * Handle invoice.payment_succeeded event.
     * This is triggered for recurring payments (monthly subscriptions).
     */
    protected function handleInvoicePaymentSucceeded($invoice): void
    {
        $stripeSubscriptionId = $invoice->subscription ?? ($invoice->lines->data[0]->subscription ?? null);

        if (! $stripeSubscriptionId) {
            Log::debug('Invoice payment succeeded but no subscription ID');
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();

        if (! $subscription) {
            // ⬅️ هنا التثبيت الصحيح
            $subscription = Subscription::where('status', 'active')
                ->whereNull('stripe_subscription_id')
                ->latest()
                ->first();

            if ($subscription) {
                $subscription->update([
                    'stripe_subscription_id' => $stripeSubscriptionId,
                ]);
            } else {
                Log::debug('No local subscription available to attach Stripe subscription');
                return;
            }
        }

        // Check if payment already recorded
        $existingPayment = Payment::where('transaction_id', $invoice->id)->first();
        if ($existingPayment) {
            Log::debug('Payment already recorded for invoice', ['invoice_id' => $invoice->id]);
            return;
        }

        // Create payment record
        Payment::create([
            'subscription_id' => $subscription->id,
            'coupon_id'       => null,
            'amount'          => $invoice->amount_due / 100,
            'discount'        => ($invoice->amount_due - $invoice->amount_paid) / 100,
            'total'           => $invoice->amount_paid / 100,
            'payment_method'  => 'stripe',
            'transaction_id'  => $invoice->id,
            'status'          => 'completed',
        ]);

        // If subscription was in grace period, reactivate it
        if ($subscription->isInGracePeriod()) {
            $this->paymentFailureService->reactivateSubscription($subscription);
        }

        Log::info("Payment recorded for invoice: {$invoice->id}, subscription: {$subscription->id}");
    }

    /**
     * Handle invoice.payment_failed event.
     * This is triggered when payment fails.
     */
    protected function handleInvoicePaymentFailed($invoice): void
    {
        $stripeSubscriptionId = $invoice->subscription ?? null;

        if (! $stripeSubscriptionId) {
            Log::error("invoice.payment_failed: Missing Stripe subscription ID");
            return;
        }

        // Find our subscription
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();

        if (! $subscription) {
            Log::error("invoice.payment_failed: Subscription not found for Stripe ID: {$stripeSubscriptionId}");
            return;
        }

        // Create payment failure record
        Payment::create([
            'subscription_id' => $subscription->id,
            'coupon_id'       => null,
            'amount'          => $invoice->amount_due / 100,
            'discount'        => 0,
            'total'           => $invoice->amount_due / 100,
            'payment_method'  => $invoice->payment_intent ?? null,
            'transaction_id'  => $invoice->id,
            'status'          => 'failed',
        ]);

        // Handle payment failure using PaymentFailureService
        $this->paymentFailureService->handlePaymentFailure($subscription, (array) $invoice);

        Log::error("Payment failed for subscription {$subscription->id}, invoice: {$invoice->id}");
    }

    protected function handleSubscriptionDeleted($stripeSubscription): void
    {
        $subscription = Subscription::where(
            'stripe_subscription_id',
            $stripeSubscription->id
        )->first();

        if (! $subscription) {
            Log::warning('Stripe subscription deleted but local subscription not found', [
                'stripe_subscription_id' => $stripeSubscription->id,
            ]);
            return;
        }

        // ⛔ حماية من التكرار
        if ($subscription->status === 'cancelled') {
            return;
        }

        // حفظ الحالة القديمة
        $oldStatus = $subscription->status;

        // تحديث الحالة
        $subscription->update([
            'status'               => 'cancelled',
            'ends_at'              => now(),
            'auto_renew'           => false,
            'cancel_at_period_end' => false,
            'suspension_reason'    => 'user_cancelled',
            'suspended_at'         => now(),
        ]);

        // 📧 إرسال الإيميل فقط إذا تغيّرت الحالة
        if ($oldStatus !== 'cancelled') {

            $user = $subscription->user;

            if ($user && $user->email) {
                \Mail::to($user->email)->send(
                    new \App\Mail\SubscriptionCancelledMail($subscription)
                );
            }
        }

        Log::info('Subscription cancelled via Stripe', [
            'subscription_id'        => $subscription->id,
            'stripe_subscription_id' => $stripeSubscription->id,
        ]);
    }

}
