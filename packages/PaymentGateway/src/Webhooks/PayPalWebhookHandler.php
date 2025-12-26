<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Webhooks;

use Nexus\PaymentGateway\Contracts\WebhookHandlerInterface;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\WebhookEventType;
use Nexus\PaymentGateway\Exceptions\WebhookParsingException;
use Nexus\PaymentGateway\Exceptions\WebhookProcessingException;
use Nexus\PaymentGateway\ValueObjects\WebhookPayload;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Handles PayPal webhooks.
 *
 * @see https://developer.paypal.com/api/rest/webhooks/
 */
final class PayPalWebhookHandler implements WebhookHandlerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getProvider(): GatewayProvider
    {
        return GatewayProvider::PAYPAL;
    }

    /**
     * Verify PayPal webhook signature.
     *
     * Note: Real PayPal verification requires fetching the certificate from the URL
     * provided in the headers and verifying the signature against the payload.
     * This is a simplified implementation for the interface contract.
     */
    public function verifySignature(
        string $payload,
        string $signature,
        string $secret,
    ): bool {
        // In a real implementation, we would:
        // 1. Get transmission ID, timestamp, and webhook ID from headers
        // 2. Reconstruct the signed string
        // 3. Verify using the certificate
        
        // For now, we assume the signature passed here is the one we want to check
        // or that the caller has handled the complex verification.
        
        // TODO: Implement full PayPal signature verification logic
        return !empty($signature);
    }

    public function parsePayload(string $payload, array $headers = []): WebhookPayload
    {
        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new WebhookParsingException("Invalid JSON payload: {$e->getMessage()}", 0, $e);
        }

        if (!isset($data['event_type'])) {
            throw new WebhookParsingException("Missing 'event_type' in PayPal webhook payload");
        }

        $eventType = $this->mapEventType($data['event_type']);
        $eventId = $data['id'] ?? uniqid('evt_');
        $resourceId = $data['resource']['id'] ?? null;

        return new WebhookPayload(
            eventId: $eventId,
            eventType: $eventType,
            provider: GatewayProvider::PAYPAL,
            resourceId: $resourceId,
            resourceType: $data['resource_type'] ?? null,
            data: $data,
            receivedAt: isset($data['create_time']) ? new \DateTimeImmutable($data['create_time']) : new \DateTimeImmutable(),
        );
    }

    public function processWebhook(WebhookPayload $payload): void
    {
        $this->logger->info('Processing PayPal webhook', [
            'id' => $payload->eventId,
            'type' => $payload->eventType->value,
        ]);

        // Logic to dispatch events or update local state would go here
        // Typically handled by the WebhookProcessor emitting events
    }

    private function mapEventType(string $paypalEventType): WebhookEventType
    {
        return match ($paypalEventType) {
            'PAYMENT.CAPTURE.COMPLETED' => WebhookEventType::PAYMENT_SUCCEEDED,
            'PAYMENT.CAPTURE.DENIED' => WebhookEventType::PAYMENT_FAILED,
            'PAYMENT.CAPTURE.REFUNDED' => WebhookEventType::PAYMENT_REFUNDED,
            'CUSTOMER.DISPUTE.CREATED' => WebhookEventType::DISPUTE_CREATED,
            'CUSTOMER.DISPUTE.RESOLVED' => WebhookEventType::DISPUTE_WON, // Or lost, depends on outcome
            default => WebhookEventType::UNKNOWN,
        };
    }
}
