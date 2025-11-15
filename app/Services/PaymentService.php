<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Exception;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a payment intent for booking
     */
    public function createPaymentIntent($amount, $currency = 'usd', $metadata = [])
    {
        try {
            // Convert amount to cents (Stripe requires smallest currency unit)
            $amountInCents = (int)($amount * 100);

            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => $currency,
                'metadata' => $metadata,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            Log::info('Payment Intent created', [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
                'metadata' => $metadata
            ]);

            return [
                'success' => true,
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'amount' => $amount,
            ];

        } catch (Exception $e) {
            Log::error('Payment Intent creation failed', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'metadata' => $metadata
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieve payment intent status
     */
    public function getPaymentIntent($paymentIntentId)
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            return [
                'success' => true,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount / 100,
                'payment_intent' => $paymentIntent,
            ];

        } catch (Exception $e) {
            Log::error('Failed to retrieve payment intent', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel payment intent
     */
    public function cancelPaymentIntent($paymentIntentId)
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            
            if ($paymentIntent->status === 'requires_payment_method' || 
                $paymentIntent->status === 'requires_confirmation') {
                $paymentIntent->cancel();
                
                Log::info('Payment Intent cancelled', [
                    'payment_intent_id' => $paymentIntentId
                ]);
                
                return true;
            }

            return false;

        } catch (Exception $e) {
            Log::error('Failed to cancel payment intent', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Refund a payment
     */
    public function refundPayment($paymentIntentId, $amount = null)
    {
        try {
            $refundData = ['payment_intent' => $paymentIntentId];
            
            if ($amount) {
                $refundData['amount'] = (int)($amount * 100);
            }

            $refund = \Stripe\Refund::create($refundData);

            Log::info('Payment refunded', [
                'payment_intent_id' => $paymentIntentId,
                'refund_id' => $refund->id,
                'amount' => $amount
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
            ];

        } catch (Exception $e) {
            Log::error('Refund failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}