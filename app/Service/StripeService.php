<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret'));
    }

    /**
     * Create a payment intent for Stripe
     */
    public function createPaymentIntent(Order $order): array
    {
        try {
            // Create a PaymentIntent with the order amount and currency
            $paymentIntent = PaymentIntent::create([
                'amount' => $this->formatAmount($order->total_price),
                'currency' => 'usd',
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            // Create a payment record in the database
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_type' => 'stripe',
                'status' => 'en attente',
                'transaction_id' => $paymentIntent->id,
                'amount' => $order->total_price,
                'currency' => 'usd',
                'stripe_payment_intent_id' => $paymentIntent->id,
            ]);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_id' => $payment->id,
                'payment_intent_id' => $paymentIntent->id,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process a payment with card details (for testing)
     * This method is for backend testing only, not production use
     */
    public function processTestPayment(Order $order, string $cardNumber = '4242424242424242'): array
    {
        try {
            // Create a payment method with test card details
            $paymentMethod = PaymentMethod::create([
                'type' => 'card',
                'card' => [
                    'number' => $cardNumber,
                    'exp_month' => 12,
                    'exp_year' => 2025,
                    'cvc' => '123',
                ],
            ]);

            // Create a payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $this->formatAmount($order->total_price),
                'currency' => 'usd',
                'payment_method' => $paymentMethod->id,
                'confirm' => true,
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                ],
            ]);

            // Create or update payment record
            $payment = Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'payment_type' => 'stripe',
                    'status' => $paymentIntent->status === 'succeeded' ? 'réussi' : 'en attente',
                    'transaction_id' => $paymentIntent->id,
                    'amount' => $order->total_price,
                    'currency' => 'usd',
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'payment_details' => [
                        'payment_method' => $paymentMethod->id,
                        'status' => $paymentIntent->status,
                        'receipt_url' => $paymentIntent->charges->data[0]->receipt_url ?? null,
                    ],
                ]
            );

            // Update order status if payment succeeded
            if ($paymentIntent->status === 'succeeded') {
                $order->status = 'en cours';
                $order->save();
            }

            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
                'payment' => $payment,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieve a payment intent status
     */
    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            
            // Update our local payment record
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();
            if ($payment) {
                $payment->status = $paymentIntent->status === 'succeeded' ? 'réussi' : 'en attente';
                $payment->save();
                
                // Update order status if payment succeeded
                if ($paymentIntent->status === 'succeeded') {
                    $order = $payment->order;
                    if ($order) {
                        $order->status = 'en cours';
                        $order->save();
                    }
                }
            }
            
            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
                'payment' => $payment,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Retrieve Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format amount for Stripe (convert to cents)
     */
    private function formatAmount($amount): int
    {
        // Stripe requires amounts in cents
        return (int)($amount * 100);
    }
}
