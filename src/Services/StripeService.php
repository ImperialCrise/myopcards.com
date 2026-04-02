<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StripeAccount;
use App\Models\Wallet;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Webhook;

class StripeService
{
    private static bool $initialized = false;

    private static function init(): void
    {
        if (!self::$initialized) {
            Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? '');
            self::$initialized = true;
        }
    }

    public static function getPublishableKey(): string
    {
        return $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '';
    }

    public static function createCustomer(int $userId, string $email, ?string $name = null): string
    {
        self::init();

        $params = ['email' => $email, 'metadata' => ['user_id' => (string) $userId]];
        if ($name !== null) {
            $params['name'] = $name;
        }

        $customer = Customer::create($params);
        $customerId = $customer->id;

        StripeAccount::createOrUpdate($userId, $customerId);

        return $customerId;
    }

    public static function createPaymentIntent(int $userId, float $amount, string $currency = 'usd', ?string $description = null): array
    {
        self::init();

        // Ensure customer exists
        $stripeAccount = StripeAccount::findByUserId($userId);
        $customerId = null;
        if ($stripeAccount && !empty($stripeAccount['stripe_customer_id'])) {
            $customerId = $stripeAccount['stripe_customer_id'];
        }

        // Amount must be in cents
        $amountCents = (int) round($amount * 100);

        $params = [
            'amount' => $amountCents,
            'currency' => $currency,
            'metadata' => [
                'user_id' => (string) $userId,
                'type' => 'wallet_deposit',
            ],
        ];

        if ($customerId) {
            $params['customer'] = $customerId;
        }
        if ($description) {
            $params['description'] = $description;
        }

        $paymentIntent = PaymentIntent::create($params);

        return [
            'client_secret' => $paymentIntent->client_secret,
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $amount,
            'amount_cents' => $amountCents,
            'currency' => $currency,
        ];
    }

    public static function confirmPaymentIntent(string $paymentIntentId): array
    {
        self::init();

        $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

        return [
            'id' => $paymentIntent->id,
            'status' => $paymentIntent->status,
            'amount' => $paymentIntent->amount / 100,
            'amount_cents' => $paymentIntent->amount,
            'currency' => $paymentIntent->currency,
            'metadata' => $paymentIntent->metadata->toArray(),
        ];
    }

    public static function handleWebhook(string $payload, string $sigHeader): array
    {
        $endpointSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            return ['success' => false, 'error' => 'Invalid payload.'];
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return ['success' => false, 'error' => 'Invalid signature.'];
        }

        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $userId = (int) ($paymentIntent->metadata->user_id ?? 0);
                $amount = $paymentIntent->amount / 100;

                if ($userId > 0 && $amount > 0) {
                    Wallet::ensureWallet($userId);
                    Wallet::creditAvailable(
                        $userId,
                        $amount,
                        'deposit',
                        'stripe',
                        null,
                        'Stripe deposit',
                        $paymentIntent->id
                    );
                }

                return ['success' => true, 'event' => 'payment_intent.succeeded', 'user_id' => $userId, 'amount' => $amount];

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $userId = (int) ($paymentIntent->metadata->user_id ?? 0);

                return ['success' => true, 'event' => 'payment_intent.payment_failed', 'user_id' => $userId];

            default:
                return ['success' => true, 'event' => $event->type, 'message' => 'Unhandled event type.'];
        }
    }
}
