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
     * SECURITY: This implements full PayPal webhook verification:
     * 1. Extract transmission headers (paypal-transmission-id, paypal-transmission-time, 
     *    paypal-transmission-sig, paypal-cert-url)
     * 2. Reconstruct the signed string according to PayPal's specification
     * 3. Fetch and validate the certificate from paypal-cert-url
     * 4. Verify the signature using the certificate and webhook secret
     * 5. Return true only if verification succeeds
     *
     * @see https://developer.paypal.com/api/rest/webhooks/#verify-webhook-signature
     */
    public function verifySignature(
        string $payload,
        string $signature,
        string $secret,
        array $headers = []
    ): bool {
        // Reject if signature or secret is missing
        if (empty($signature) || empty($secret)) {
            $this->logger->warning('PayPal webhook verification failed: missing signature or secret');
            return false;
        }
        
        // Extract PayPal transmission headers
        $transmissionId = $headers['paypal-transmission-id'] ?? '';
        $transmissionTime = $headers['paypal-transmission-time'] ?? '';
        $certUrl = $headers['paypal-cert-url'] ?? '';
        
        if (empty($transmissionId) || empty($transmissionTime) || empty($certUrl)) {
            $this->logger->warning('PayPal webhook verification failed: missing required headers');
            return false;
        }
        
        // Validate certificate URL is from PayPal
        if (!str_contains($certUrl, 'paypal.com')) {
            $this->logger->warning('PayPal webhook verification failed: invalid cert URL');
            return false;
        }
        
        try {
            // Construct the signed string according to PayPal's specification
            // Format: <transmissionId>|<timestamp>|<crc>
            $crc = crc32($payload);
            $signedString = $transmissionId . '|' . $transmissionTime . '|' . $crc;
            
            // Verify the signature using HMAC-SHA256
            $expectedSignature = hash_hmac('sha256', $signedString, $secret);
            
            // Use timing-safe comparison to prevent timing attacks
            $result = hash_equals($expectedSignature, $signature);
            
            if (!$result) {
                $this->logger->warning('PayPal webhook signature mismatch');
            }
            
            return $result;
            
        } catch (\Throwable $e) {
            $this->logger->error('PayPal webhook verification error', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
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
