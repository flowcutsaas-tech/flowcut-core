<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Services\SubscriptionValidationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Stripe\StripeClient;

class CheckoutController extends Controller
{
    protected $subscriptionService;
    protected $subscriptionValidationService;
    protected $stripe;

    public function __construct(
        SubscriptionService $subscriptionService,
        SubscriptionValidationService $subscriptionValidationService
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->subscriptionValidationService = $subscriptionValidationService;
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Get all available plans.
     */
    public function getPlans(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'plans' => $this->subscriptionService->getAllPlans(),
        ]);
    }

    /**
     * Get checkout status for current user
     * Check for active and pending subscriptions
     */
    public function getCheckoutStatus(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Check for active subscription
        $activeSubscription = $this->subscriptionValidationService->getActiveSubscription($user);
        $pendingPayment = $this->subscriptionValidationService->hasPendingPayment($user);

        return response()->json([
            'success' => true,
            'has_active_subscription' => (bool)$activeSubscription,
            'active_subscription' => $activeSubscription ? [
                'id' => $activeSubscription->id,
                'plan_id' => $activeSubscription->plan_id,
                'status' => $activeSubscription->status,
                'ends_at' => $activeSubscription->ends_at,
            ] : null,
            'pending_payment' => $pendingPayment['has_pending'] ? [
                'subscription_id' => $pendingPayment['subscription_id'],
                'plan_id' => $pendingPayment['plan_id'],
                'price' => $pendingPayment['price'],
                'failed_attempts' => $pendingPayment['failed_attempts'],
                'message' => $pendingPayment['message'],
            ] : null,
        ]);
    }


    public function createCheckoutSession(Request $request): JsonResponse
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'plan' => 'required|in:basic,professional,advanced',
            'term' => 'required|in:12,24',
            'barbers' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if user can purchase a new subscription
        $canPurchase = $this->subscriptionValidationService->canPurchaseNewSubscription($user);
        
        if (!$canPurchase['can_purchase']) {
            return response()->json([
                'success' => false,
                'message' => $canPurchase['message'],
                'reason' => $canPurchase['reason'],
                'current_subscription' => $canPurchase['current_subscription'] ?? null,
            ], 409); // Conflict status
        }

        // Check if user has pending payment (payment failed previously)
        $pendingPayment = $this->subscriptionValidationService->hasPendingPayment($user);
        
        if ($pendingPayment['has_pending']) {
            // If trying to purchase same plan as pending, resume checkout
            if ($pendingPayment['plan_id'] === $request->plan_id) {
                return $this->resumePendingCheckout($user, $pendingPayment['subscription_id']);
            }
            
            // If trying to purchase different plan, inform about pending subscription
            return response()->json([
                'success' => false,
                'message' => 'You have a pending purchase. Please complete it first or cancel it.',
                'reason' => 'pending_subscription',
                'pending_subscription' => $pendingPayment,
            ], 409);
        }

        // Proceed with creating new checkout session
        return $this->createNewCheckoutSession($user, $request);
    }

    /**
     * Resume pending checkout (payment failed previously)
     */
    private function resumePendingCheckout($user, $subscriptionId): JsonResponse
    {
        try {
            $subscription = Subscription::findOrFail($subscriptionId);
            $plan = $this->subscriptionService->getPlanDetails($subscription->plan_id);

            // Create new Stripe session
            $session = $this->stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'mode' => 'subscription',
                'customer_email' => $user->email,
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $plan['name'] . ' Plan',
                            'description' => 'Barbershop Management System - ' . $plan['name'] . ' Plan',
                        ],
                        'unit_amount' => (int)($plan['price'] * 100),
                        'recurring' => [
                            'interval' => 'month',
                            'interval_count' => 1,
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => config('app.frontend_url') . '/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.frontend_url') . '/checkout?resumed=true&subscription_id=' . $subscriptionId,
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_id' => $subscription->plan_id,
                    'subscription_id' => $subscriptionId,
                    'is_resumed' => 'true',
                ],
            ]);

            return response()->json([
                'success' => true,
                'session_id' => $session->id,
                'message' => 'Payment session ready. Please complete your payment.',
                'is_resumed' => true,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating payment session: ' . $e->getMessage(),
            ], 500);
        }
    }
private function resolveStripePrice(string $plan, int $term): string
{
    $key = "{$plan}_{$term}";

    $priceId = config("services.stripe.prices.$key");

    if (!$priceId) {
        throw new \Exception("Stripe price not configured: $key");
    }

    return $priceId;
}


    /**
     * Create new checkout session
     */
 private function createNewCheckoutSession($user, Request $request): JsonResponse
{
    try {
        logger()->info('Checkout payload', $request->all());

        $plan    = $request->plan;          // basic | professional | advanced
        $term    = (int) $request->term;    // 12 | 24
        $barbers = (int) ($request->barbers ?? 0);

        // 1️⃣ resolve base plan price
        $basePriceKey = "{$plan}_{$term}";
        $basePriceId  = config("services.stripe.prices.$basePriceKey");

        if (!$basePriceId) {
            throw new \Exception("Stripe price not configured: $basePriceKey");
        }

        // 2️⃣ tax rate
        $taxRateId = config('services.stripe.tax_rate');

        if (!$taxRateId) {
            throw new \Exception("Stripe tax rate not configured");
        }

        // 3️⃣ create customer (required for tax)
        $customer = $this->stripe->customers->create([
            'email' => $user->email,
        ]);

        // 4️⃣ line items
        $lineItems = [[
            'price' => $basePriceId,
            'quantity' => 1,
            'tax_rates' => [$taxRateId],
        ]];

        // 5️⃣ extra barbers logic
        if ($plan === 'professional' && $barbers > 2) {
            $extraQty = $barbers - 2;
            $extraKey = "professional_extra_{$term}";
            $extraPriceId = config("services.stripe.prices.$extraKey");

            if (!$extraPriceId) {
                throw new \Exception("Stripe price not configured: $extraKey");
            }

            $lineItems[] = [
                'price' => $extraPriceId,
                'quantity' => $extraQty,
                'tax_rates' => [$taxRateId],
            ];
        }

        if ($plan === 'advanced' && $barbers > 5) {
            $extraQty = $barbers - 5;
            $extraKey = "advanced_extra_{$term}";
            $extraPriceId = config("services.stripe.prices.$extraKey");

            if (!$extraPriceId) {
                throw new \Exception("Stripe price not configured: $extraKey");
            }

            $lineItems[] = [
                'price' => $extraPriceId,
                'quantity' => $extraQty,
                'tax_rates' => [$taxRateId],
            ];
        }

        // 6️⃣ create checkout session
        $session = $this->stripe->checkout->sessions->create([
            'customer' => $customer->id,
            'mode' => 'subscription',
            'line_items' => $lineItems,
  // 👇 هذا السطر فقط
    'allow_promotion_codes' => true,
            'success_url' => config('app.frontend_url') . '/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => config('app.frontend_url') . '/checkout',

            'metadata' => [
                'user_id' => $user->id,
                'plan'    => $plan,
                'term'    => $term,
                'barbers' => $barbers,
            ],
        ]);

        return response()->json([
            'success' => true,
            'redirect_url' => $session->url,
        ]);

    } catch (\Exception $e) {
        logger()->error('Stripe checkout error', [
            'message' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Unable to start secure checkout.',
        ], 500);
    }
}


}
