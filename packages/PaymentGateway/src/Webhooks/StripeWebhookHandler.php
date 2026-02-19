<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Webhooks;

use Nexus\PaymentGateway\Contracts\WebhookHandlerInterface;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\TransactionStatus;
use Nexus\PaymentGateway\Enums\WebhookEventType;
use Nexus\PaymentGateway\Exceptions\WebhookParsingException;
use Nexus\PaymentGateway\Exceptions\WebhookVerificationFailedException;
use Nexus\PaymentGateway\ValueObjects\WebhookEvent;
use Nexus\PaymentGateway\ValueObjects\WebhookPayload;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Stripe Webhook Handler.
 *
 * Handles Stripe webhook events with proper signature verification.
 * 
 * @see https://stripe.com/docs/webhooks/signatures
 */
final class StripeWebhookHandler implements WebhookHandlerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getProvider(): GatewayProvider
    {
        return GatewayProvider::STRIPE;
    }

    /**
     * Verify Stripe webhook signature.
     *
     * SECURITY: This implements fail-closed verification for Stripe webhooks.
     * Stripe uses ECDSA with SHA-256 for signature verification.
     * 
     * Current implementation throws an exception to prevent insecure fallback.
     * TODO: Implement proper Stripe ECDSA signature verification using:
     * - OpenSSL with ECDSA (secp256k1 curve)
     * - Or stripe-php library's signature verification
     *
     * @see https://stripe.com/docs/webhooks/signatures
     * 
     * @param string $payload Raw request body
     * @param string $signature Stripe-Signature header value
     * @param string $secret Webhook secret from Stripe dashboard
     * @param array $headers Request headers (including timestamp)
     * @return bool True if signature is valid
     * @throws \RuntimeException When signature verification is not properly implemented
     */
    public function verifySignature(
        string $payload,
        string $signature,
        string $secret,
        array $headers = []
    ): bool {
        // Reject if signature or secret is missing
        if (empty($signature) || empty($secret)) {
            $this->logger->warning('Stripe webhook verification failed: missing signature or secret');
            return false;
        }

        // CRITICAL: Throw exception to enforce fail-closed behavior
        // DO NOT silently fall back to HMAC - this is insecure!
        throw new \RuntimeException(
            'Stripe webhook signature verification not properly implemented. ' .
            'Current implementation uses HMAC-SHA256 which is insecure. ' .
            'Must implement ECDSA verification using OpenSSL or stripe-php library. ' .
            'See: https://stripe.com/docs/webhooks/signatures'
        );
    }

    public function handle(array $payload, array $headers = []): WebhookEvent
    {
        $type = $payload['type'] ?? 'unknown';
        $data = $payload['data']['object'] ?? [];
        $id = $data['id'] ?? null;

        $status = match ($type) {
            'payment_intent.succeeded' => TransactionStatus::COMPLETED,
            'payment_intent.payment_failed' => TransactionStatus::FAILED,
            'payment_intent.canceled' => TransactionStatus::CANCELLED,
            'charge.refunded' => TransactionStatus::REFUNDED,
            default => TransactionStatus::PENDING,
        };

        return new WebhookEvent(
            transactionId: $id,
            status: $status,
            eventType: $type,
            rawPayload: $payload
        );
    }

    public function parsePayload(string $payload, array $headers = []): WebhookPayload
    {
        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new WebhookParsingException("Invalid JSON payload: {$e->getMessage()}", 0, $e);
        }

        if (!isset($data['type'])) {
            throw new WebhookParsingException("Missing 'type' in Stripe webhook payload");
        }

        $eventType = $this->mapEventType($data['type']);
        $eventId = $data['id'] ?? uniqid('evt_');
        $resourceId = $data['data']['object']['id'] ?? null;

        return new WebhookPayload(
            eventId: $eventId,
            eventType: $eventType,
            provider: GatewayProvider::STRIPE,
            resourceId: $resourceId,
            data: $data,
            receivedAt: isset($data['created']) 
                ? \DateTimeImmutable::createFromFormat('U', (string)$data['created']) 
                : new \DateTimeImmutable(),
        );
    }

    public function processWebhook(WebhookPayload $payload): void
    {
        $this->logger->info('Processing Stripe webhook', [
            'id' => $payload->eventId,
            'type' => $payload->eventType->value,
        ]);
    }

    public function supports(string $provider): bool
    {
        return $provider === 'stripe';
    }

    private function mapEventType(string $stripeEventType): WebhookEventType
    {
        return match ($stripeEventType) {
            'payment_intent.succeeded' => WebhookEventType::PAYMENT_SUCCEEDED,
            'payment_intent.payment_failed' => WebhookEventType::PAYMENT_FAILED,
            'payment_intent.canceled' => WebhookEventType::PAYMENT_CANCELLED,
            'charge.refunded' => WebhookEventType::PAYMENT_REFUNDED,
            'charge.dispute.created' => WebhookEventType::DISPUTE_CREATED,
            'charge.dispute.closed' => WebhookEventType::DISPUTE_CLOSED,
            'customer.subscription.created' => WebhookEventType::SUBSCRIPTION_CREATED,
            'customer.subscription.deleted' => WebhookEventType::SUBSCRIPTION_CANCELLED,
            'invoice.paid' => WebhookEventType::INVOICE_PAID,
            'invoice.payment_failed' => WebhookEventType::PAYMENT_FAILED,
            default => WebhookEventType::UNKNOWN,
        };
    }
}
