<?php

declare(strict_types=1);

namespace App\Connectors\Adapters;

use Nexus\Connector\Contracts\PaymentGatewayConnectorInterface;
use Nexus\Connector\Exceptions\{ConnectionException, PaymentDeclinedException};
use Stripe\{StripeClient, Exception\ApiErrorException};

/**
 * Stripe payment gateway adapter.
 *
 * This adapter translates the generic PaymentGatewayConnectorInterface
 * into Stripe-specific API calls.
 */
final class StripePaymentAdapter implements PaymentGatewayConnectorInterface
{
    private StripeClient $client;

    /**
     * @param string $apiKey Stripe secret API key
     * @param string $apiVersion Stripe API version (default: latest)
     */
    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiVersion = '2023-10-16'
    ) {
        $this->client = new StripeClient([
            'api_key' => $this->apiKey,
            'stripe_version' => $this->apiVersion,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function charge(float $amount, string $currency, array $paymentMethod, array $options = []): array
    {
        try {
            $amountInCents = (int) round($amount * 100);

            $params = [
                'amount' => $amountInCents,
                'currency' => strtolower($currency),
                'source' => $paymentMethod['token'] ?? $paymentMethod['source'] ?? null,
                'description' => $options['description'] ?? null,
                'metadata' => $options['metadata'] ?? [],
            ];

            if (isset($options['customer'])) {
                $params['customer'] = $options['customer'];
            }

            if (isset($options['idempotency_key'])) {
                $idempotencyKey = $options['idempotency_key'];
            } else {
                $idempotencyKey = uniqid('charge_', true);
            }

            $charge = $this->client->charges->create($params, [
                'idempotency_key' => $idempotencyKey,
            ]);

            return [
                'transaction_id' => $charge->id,
                'status' => $charge->status,
                'amount' => $charge->amount / 100,
            ];

        } catch (ApiErrorException $e) {
            if ($e->getStripeCode() === 'card_declined') {
                throw PaymentDeclinedException::declined(
                    reason: $e->getMessage(),
                    code: $e->getStripeCode()
                );
            }

            throw ConnectionException::requestFailed(
                message: "Stripe charge failed: {$e->getMessage()}",
                httpStatusCode: $e->getHttpStatus(),
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function refund(string $transactionId, ?float $amount = null, array $options = []): array
    {
        try {
            $params = ['charge' => $transactionId];

            if ($amount !== null) {
                $params['amount'] = (int) round($amount * 100);
            }

            if (isset($options['reason'])) {
                $params['reason'] = $options['reason'];
            }

            $refund = $this->client->refunds->create($params);

            return [
                'refund_id' => $refund->id,
                'status' => $refund->status,
                'amount' => $refund->amount / 100,
            ];

        } catch (ApiErrorException $e) {
            throw ConnectionException::requestFailed(
                message: "Stripe refund failed: {$e->getMessage()}",
                httpStatusCode: $e->getHttpStatus(),
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createPaymentIntent(float $amount, string $currency, array $options = []): array
    {
        try {
            $amountInCents = (int) round($amount * 100);

            $params = [
                'amount' => $amountInCents,
                'currency' => strtolower($currency),
                'automatic_payment_methods' => ['enabled' => true],
            ];

            if (isset($options['customer'])) {
                $params['customer'] = $options['customer'];
            }

            if (isset($options['description'])) {
                $params['description'] = $options['description'];
            }

            if (isset($options['metadata'])) {
                $params['metadata'] = $options['metadata'];
            }

            if (isset($options['capture_method'])) {
                $params['capture_method'] = $options['capture_method'];
            }

            $intent = $this->client->paymentIntents->create($params);

            return [
                'intent_id' => $intent->id,
                'client_secret' => $intent->client_secret,
                'status' => $intent->status,
            ];

        } catch (ApiErrorException $e) {
            throw ConnectionException::requestFailed(
                message: "Stripe payment intent creation failed: {$e->getMessage()}",
                httpStatusCode: $e->getHttpStatus(),
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function captureAuthorization(string $intentId, ?float $amount = null): array
    {
        try {
            $params = [];

            if ($amount !== null) {
                $params['amount_to_capture'] = (int) round($amount * 100);
            }

            $intent = $this->client->paymentIntents->capture($intentId, $params);

            return [
                'transaction_id' => $intent->id,
                'status' => $intent->status,
                'amount' => ($intent->amount_received ?? $intent->amount) / 100,
            ];

        } catch (ApiErrorException $e) {
            throw ConnectionException::requestFailed(
                message: "Stripe payment capture failed: {$e->getMessage()}",
                httpStatusCode: $e->getHttpStatus(),
                previous: $e
            );
        }
    }
}
