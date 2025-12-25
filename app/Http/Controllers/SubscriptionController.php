<?php
namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\StripeClient;

class SubscriptionController extends Controller
{
    /**
     * Display the current subscription status.
     */
    public function show(Request $request): JsonResponse
    {
        $tenant = Tenant::where('user_id', Auth::id())
            ->with('currentSubscription')
            ->first();
        if (! $tenant) {
            return response()->json([
                'success'       => true,
                'subscription'  => null,
                'tenant_status' => null,
            ]);
        }

        return response()->json([
            'success'       => true,
            'subscription'  => $tenant->currentSubscription,
            'tenant_status' => $tenant->status,
        ]);
    }

    public function history(): JsonResponse
    {
        $subscriptions = \App\Models\Subscription::where('user_id', Auth::id())
            ->orderByDesc('starts_at')
            ->get();

        return response()->json([
            'success'       => true,
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Get detailed invoices from Stripe with line items and discounts.
     */
    public function getInvoices(): JsonResponse
    {
        try {
            $user   = Auth::user();
            $tenant = Tenant::where('user_id', $user->id)
                ->with('subscription')
                ->first();

            if (! $tenant || ! $tenant->subscription || ! $tenant->subscription->stripe_subscription_id) {
                return response()->json([
                    'success'  => true,
                    'invoices' => [],
                ]);
            }
            $stripe = new StripeClient(config('services.stripe.secret'));

            // Get the Stripe customer ID
            $stripeSubscription = $stripe->subscriptions->retrieve($tenant->subscription->stripe_subscription_id);
            $customerId         = $stripeSubscription->customer;

            // Fetch invoices from Stripe
            $stripeInvoices = $stripe->invoices->all([
                'customer' => $customerId,
                'limit'    => 100,
            ]);

            // Transform invoices to include detailed information
            $invoices = collect($stripeInvoices->data)->map(function ($invoice) {
                $lineItems     = [];
                $totalDiscount = 0;

                // Get line items for this invoice
                if ($invoice->lines) {
                    foreach ($invoice->lines->data as $lineItem) {
                        $lineItems[] = [
                            'description' => $lineItem->description,
                            'amount'      => $lineItem->amount / 100,
                            'quantity'    => $lineItem->quantity ?? 1,
                            'period'      => isset($lineItem->period) ? [
                                'start' => date('Y-m-d', $lineItem->period->start),
                                'end'   => date('Y-m-d', $lineItem->period->end),
                            ] : null,
                        ];
                    }
                }

                // Calculate discount
                if ($invoice->discount) {
                    $totalDiscount = $invoice->discount->coupon->amount_off ?? 0;
                    if ($invoice->discount->coupon->percent_off) {
                        $totalDiscount = ($invoice->subtotal / 100) * ($invoice->discount->coupon->percent_off / 100);
                    }
                }

                return [
                    'id'                 => $invoice->id,
                    'number'             => $invoice->number,
                    'date'               => date('Y-m-d', $invoice->created),
                    'due_date'           => $invoice->due_date ? date('Y-m-d', $invoice->due_date) : null,
                    'subtotal'           => $invoice->subtotal / 100,
                    'discount'           => $totalDiscount / 100,
                    'tax'                => ($invoice->tax / 100) ?? 0,
                    'total'              => $invoice->total / 100,
                    'amount_paid'        => $invoice->amount_paid / 100,
                    'amount_remaining'   => $invoice->amount_remaining / 100,
                    'status'             => $invoice->status,
                    'paid'               => $invoice->paid,
                    'currency'           => strtoupper($invoice->currency),
                    'pdf_url'            => $invoice->invoice_pdf,
                    'hosted_invoice_url' => $invoice->hosted_invoice_url,
                    'line_items'         => $lineItems,
                    'coupon'             => $invoice->discount ? [
                        'code'        => $invoice->discount->coupon->id,
                        'amount_off'  => $invoice->discount->coupon->amount_off ? ($invoice->discount->coupon->amount_off / 100) : null,
                        'percent_off' => $invoice->discount->coupon->percent_off,
                    ] : null,
                ];
            })->toArray();

            return response()->json([
                'success'  => true,
                'invoices' => $invoices,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch invoices: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch invoices',
            ], 500);
        }
    }

    public function invoicesBySubscription(string $stripeSubscriptionId): JsonResponse
    {
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

            $invoices = $stripe->invoices->all([
                'subscription' => $stripeSubscriptionId,
                'limit'        => 50,
            ]);

            $data = collect($invoices->data)->map(function ($invoice) {
                return [
                    'id'                 => $invoice->id,
                    'number'             => $invoice->number,
                    'status'             => $invoice->status,
                    'paid'               => $invoice->paid,
                    'total'              => $invoice->total / 100,
                    'currency'           => strtoupper($invoice->currency),
                    'date'               => date('Y-m-d', $invoice->created),
                    'pdf_url'            => $invoice->invoice_pdf,
                    'hosted_invoice_url' => $invoice->hosted_invoice_url,
                ];
            });

            return response()->json([
                'success'  => true,
                'invoices' => $data,
            ]);

        } catch (\Exception $e) {
            \Log::error('Invoice fetch failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Display the API keys and URLs.
     */

    public function apiKeys(): JsonResponse
    {
        $tenant = Tenant::where('user_id', Auth::id())->first();

        if (! $tenant) {
            return response()->json([
                'success'           => true,
                'api_keys'          => null,
                'urls'              => null,
                'tenant_identifier' => null,
            ]);
        }

        return response()->json([
            'success'           => true,
            'api_keys'          => [
                'dashboard_api_key' => $tenant->dashboard_api_key,
                'booking_api_key'   => $tenant->booking_api_key,
            ],
            'urls'              => [
                'dashboard_url' => $tenant->dashboard_url,
                'booking_url'   => $tenant->booking_url,
            ],
            'tenant_identifier' => $tenant->unique_identifier,
        ]);
    }

    /**
     * Regenerate API keys.
     */
    public function regenerateApiKeys(Request $request): JsonResponse
    {
        $tenant = Tenant::where('user_id', Auth::id())->firstOrFail();

        $tenant->update([
            'dashboard_api_key' => 'dk_' . Str::random(32),
            'booking_api_key'   => 'bk_' . Str::random(32),
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'API keys regenerated successfully.',
            'api_keys' => [
                'dashboard_api_key' => $tenant->dashboard_api_key,
                'booking_api_key'   => $tenant->booking_api_key,
            ],
        ]);
    }

    /**
     * Create a Stripe Customer Portal Session.
     * Allows users to manage their subscription directly in Stripe.
     */
    public function createPortalSession(Request $request): JsonResponse
    {
        try {
            $user         = Auth::user();
            $tenant       = Tenant::where('user_id', $user->id)->firstOrFail();
            $subscription = $tenant->subscription;

            if (! $subscription || ! $subscription->stripe_subscription_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active subscription found.',
                ], 404);
            }

            $stripe = new StripeClient(config('services.stripe.secret'));

            // Get the Stripe customer ID from the subscription
            $stripeSubscription = $stripe->subscriptions->retrieve($subscription->stripe_subscription_id);
            $customerId         = $stripeSubscription->customer;

            // Create a billing portal session
            $session = $stripe->billingPortal->sessions->create([
                'customer'   => $customerId,
                'return_url' => config('app.frontend_url') . '/dashboard',
            ]);

            return response()->json([
                'success'    => true,
                'portal_url' => $session->url,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to create portal session: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create portal session: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancelAutoRenew(): JsonResponse
    {
        $tenant = Tenant::where('user_id', Auth::id())->with('currentSubscription')->first();

        if (! $tenant || ! $tenant->currentSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found',
            ], 404);
        }

        $subscription = $tenant->currentSubscription;

        if (! $subscription->stripe_subscription_id) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe subscription not linked',
            ], 400);
        }

        $stripe = new StripeClient(config('services.stripe.secret'));

        // 🔴 هذا هو الإلغاء الحقيقي للتجديد التلقائي في Stripe
        $stripe->subscriptions->update(
            $subscription->stripe_subscription_id,
            ['cancel_at_period_end' => true]
        );

        // ⬇️ تحديث قاعدة البيانات
        // $subscription->update([
        //     'auto_renew'           => false,
        //     'cancel_at_period_end' => true,
        // ]);

        return response()->json([
            'success' => true,
            'message' => 'Auto-renewal has been cancelled. Subscription will remain active until period end.',
        ]);
    }

    public function enableAutoRenew(): JsonResponse
    {
        $tenant = Tenant::where('user_id', Auth::id())->with('currentSubscription')->first();

        if (! $tenant || ! $tenant->currentSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found',
            ], 404);
        }

        $subscription = $tenant->currentSubscription;

        if (! $subscription->stripe_subscription_id) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe subscription not linked',
            ], 400);
        }

        $stripe = new StripeClient(config('services.stripe.secret'));

        // 🟢 إعادة التفعيل في Stripe
        $stripe->subscriptions->update(
            $subscription->stripe_subscription_id,
            ['cancel_at_period_end' => false]
        );

        // ⬇️ تحديث قاعدة البيانات
        // $subscription->update([
        //     'auto_renew'           => true,
        //     'cancel_at_period_end' => false,
        // ]);

        return response()->json([
            'success' => true,
            'message' => 'Auto-renewal has been enabled.',
        ]);
    }
    public function cancel(
        Request $request,
        TwoFactorAuthService $twoFactorAuthService
    ): JsonResponse {
        $request->validate([
            'otp_code' => 'required|string|size:6',
        ]);

        $user = Auth::user();

        // 🔒 تأكد أن 2FA مفعّل
        if (! $user->isTwoFactorEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Two-Factor Authentication is required.',
            ], 403);
        }

        // 🔐 تحقق من OTP
        if (! $twoFactorAuthService->verifyCode($user, $request->otp_code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid authentication code.',
            ], 422);
        }

        // 🧾 جلب الاشتراك الحالي
        $tenant = Tenant::where('user_id', $user->id)
            ->with('currentSubscription')
            ->first();

        if (! $tenant || ! $tenant->currentSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found.',
            ], 404);
        }

        $subscription = $tenant->currentSubscription;

        if (! $subscription->stripe_subscription_id) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe subscription not linked.',
            ], 400);
        }

        // 🚨 إلغاء فوري في Stripe (No refund / No proration)
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));

            $stripe->subscriptions->cancel(
                $subscription->stripe_subscription_id
            );

            Log::info('Subscription cancel requested via API', [
                'user_id'                => $user->id,
                'subscription_id'        => $subscription->id,
                'stripe_subscription_id' => $subscription->stripe_subscription_id,
            ]);

        } catch (\Throwable $e) {
            Log::error('Stripe subscription cancel failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription.',
            ], 500);
        }

        /**
         * ⛔ لا نعدّل قاعدة البيانات هنا
         * المصدر النهائي للحقيقة هو webhook:
         * customer.subscription.deleted
         */

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancellation processed.',
        ]);
    }

}
