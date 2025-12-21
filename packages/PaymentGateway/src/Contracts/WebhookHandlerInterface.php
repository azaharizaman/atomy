<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\ValueObjects\WebhookPayload;

/**
 * Contract for handling gateway webhooks.
 *
 * Gateways send webhooks for asynchronous events like:
 * - Payment status changes
 * - Refund completions
 * - Dispute notifications
 * - Payout notifications
 */
interface WebhookHandlerInterface
{
    /**
     * Verify webhook signature.
     *
     * @param string $payload Raw webhook payload
     * @param string $signature Signature from webhook headers
     * @param string $secret Webhook signing secret
     * @return bool Whether signature is valid
     */
    public function verifySignature(
        string $payload,
        string $signature,
        string $secret,
    ): bool;

    /**
     * Parse webhook payload.
     *
     * @param string $payload Raw webhook payload
     * @param array<string, string> $headers Webhook request headers
     * @throws \Nexus\PaymentGateway\Exceptions\WebhookParsingException
     */
    public function parsePayload(string $payload, array $headers = []): WebhookPayload;

    /**
     * Process a verified webhook.
     *
     * @throws \Nexus\PaymentGateway\Exceptions\WebhookProcessingException
     */
    public function processWebhook(WebhookPayload $payload): void;

    /**
     * Get the gateway provider this handler belongs to.
     */
    public function getProvider(): GatewayProvider;
}
