<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Webhooks;

use Nexus\PaymentGateway\Contracts\WebhookHandlerInterface;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\TransactionStatus;
use Nexus\PaymentGateway\Enums\WebhookEventType;
use Nexus\PaymentGateway\Exceptions\WebhookParsingException;
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
    private const SIGNATURE_TOLERANCE_SECONDS = 300;

    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getProvider(): GatewayProvider
    {
        return GatewayProvider::STRIPE;
    }

    public function verifySignature(
        string $payload,
        string $signature,
        string $secret,
        array $headers = []
    ): bool {
        if (empty($signature) || empty($secret)) {
            $this->logger->warning('Stripe webhook verification failed: missing signature or secret');
            return false;
        }

        try {
            $parsedHeader = $this->parseSignatureHeader($signature);
            if ($parsedHeader === null) {
                $this->logger->warning('Stripe webhook verification failed: malformed signature header');
                return false;
            }

            if (!$this->isTimestampFresh($parsedHeader['timestamp'])) {
                $this->logger->warning('Stripe webhook verification failed: stale timestamp');
                return false;
            }

            $signedPayload = $parsedHeader['timestamp'] . '.' . $payload;
            $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

            foreach ($parsedHeader['signatures'] as $candidateSignature) {
                if (hash_equals($expectedSignature, $candidateSignature)) {
                    return true;
                }
            }

            $this->logger->warning('Stripe webhook signature mismatch');
            return false;
        } catch (\Throwable $e) {
            $this->logger->error('Stripe webhook verification error', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
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
        $eventId = $data['id'] ?? null;
        if (!is_string($eventId) || $eventId === '') {
            throw new WebhookParsingException("Missing 'id' in Stripe webhook payload");
        }

        $resourceId = $data['data']['object']['id'] ?? null;
        $receivedAt = new \DateTimeImmutable();
        if (isset($data['created'])) {
            $createdAt = \DateTimeImmutable::createFromFormat('U', (string) $data['created']);
            if ($createdAt instanceof \DateTimeImmutable) {
                $receivedAt = $createdAt;
            }
        }

        return new WebhookPayload(
            eventId: $eventId,
            eventType: $eventType,
            provider: GatewayProvider::STRIPE,
            resourceId: $resourceId,
            data: $data,
            receivedAt: $receivedAt,
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
            'payment_intent.succeeded' => WebhookEventType::PAYMENT_CAPTURED,
            'payment_intent.payment_failed' => WebhookEventType::PAYMENT_FAILED,
            'payment_intent.canceled' => WebhookEventType::PAYMENT_CANCELED,
            'charge.refunded' => WebhookEventType::REFUND_COMPLETED,
            'charge.dispute.created' => WebhookEventType::DISPUTE_CREATED,
            'charge.dispute.closed' => WebhookEventType::DISPUTE_CLOSED,
            'customer.subscription.created' => WebhookEventType::UNKNOWN,
            'customer.subscription.deleted' => WebhookEventType::UNKNOWN,
            'invoice.paid' => WebhookEventType::UNKNOWN,
            'invoice.payment_failed' => WebhookEventType::PAYMENT_FAILED,
            default => WebhookEventType::UNKNOWN,
        };
    }

    /**
     * @return array{timestamp:int, signatures:list<string>}|null
     */
    private function parseSignatureHeader(string $signatureHeader): ?array
    {
        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $signatureHeader) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
            if ($key === 't' && ctype_digit($value)) {
                $timestamp = (int) $value;
                continue;
            }

            if ($key === 'v1' && $value !== '') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || $signatures === []) {
            return null;
        }

        return [
            'timestamp' => $timestamp,
            'signatures' => $signatures,
        ];
    }

    private function isTimestampFresh(int $timestamp): bool
    {
        return abs(time() - $timestamp) <= self::SIGNATURE_TOLERANCE_SECONDS;
    }
}
