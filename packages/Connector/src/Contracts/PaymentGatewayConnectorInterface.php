<?php

declare(strict_types=1);

namespace Nexus\Connector\Contracts;

/**
 * Domain interface for payment gateway providers.
 *
 * Vendors: Stripe, PayPal, Square, Braintree, etc.
 */
interface PaymentGatewayConnectorInterface
{
    /**
     * Charge a payment method.
     *
     * @param float $amount Amount to charge
     * @param string $currency Currency code (ISO 4217)
     * @param array<string, mixed> $paymentMethod Payment method details (card token, etc.)
     * @param array<string, mixed> $options Additional options (description, metadata, etc.)
     * @return array{transaction_id: string, status: string, amount: float}
     * @throws \Nexus\Connector\Exceptions\ConnectionException
     * @throws \Nexus\Connector\Exceptions\PaymentDeclinedException
     */
    public function charge(float $amount, string $currency, array $paymentMethod, array $options = []): array;

    /**
     * Refund a transaction.
     *
     * @param string $transactionId Original transaction ID
     * @param float|null $amount Amount to refund (null for full refund)
     * @param array<string, mixed> $options Additional options
     * @return array{refund_id: string, status: string, amount: float}
     * @throws \Nexus\Connector\Exceptions\ConnectionException
     */
    public function refund(string $transactionId, ?float $amount = null, array $options = []): array;

    /**
     * Create a payment intent for deferred capture.
     *
     * @param float $amount Amount to authorize
     * @param string $currency Currency code
     * @param array<string, mixed> $options Additional options
     * @return array{intent_id: string, client_secret: string, status: string}
     * @throws \Nexus\Connector\Exceptions\ConnectionException
     */
    public function createPaymentIntent(float $amount, string $currency, array $options = []): array;

    /**
     * Capture a previously authorized payment.
     *
     * @param string $intentId Payment intent ID
     * @param float|null $amount Amount to capture (null for full authorization)
     * @return array{transaction_id: string, status: string, amount: float}
     * @throws \Nexus\Connector\Exceptions\ConnectionException
     */
    public function captureAuthorization(string $intentId, ?float $amount = null): array;
}
