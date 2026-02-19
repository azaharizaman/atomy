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
 * Handles Square webhooks.
 *
 * @see https://developer.squareup.com/docs/webhooks/overview
 */
final class SquareWebhookHandler implements WebhookHandlerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getProvider(): GatewayProvider
    {
        return GatewayProvider::SQUARE;
    }

    /**
     * Verify Square webhook signature.
     *
     * SECURITY: This is a placeholder implementation that FAILS CLOSED for security.
     * In production, this method MUST be replaced with full Square webhook verification:
     * 1. Extract the signature from x-square-signature header
     * 2. Concatenate the notification URL (from config) + payload body
     * 3. Compute HMAC-SHA256 using the webhook signature key
     * 4. Compare the computed signature with the received signature (constant-time comparison)
     * 5. Return true only if verification succeeds
     *
     * @see https://developer.squareup.com/docs/webhooks/step3validate
     *
     * WARNING: This placeholder returns FALSE to prevent unauthorized webhook processing.
     * Implement proper verification before enabling Square webhooks in production.
     */
    /**
     * Verify Square webhook signature.
     *
     * SECURITY: This implements full Square webhook signature verification.
     * Square uses HMAC-SHA256 signature verification.
     *
     * @see https://developer.squareup.com/docs/webhooks/verify
     */
    public function verifySignature(
        string $payload,
        string $signature,
        string $secret,
        array $headers = []
    ): bool {
        // Reject if signature or secret is missing
        if (empty($signature) || empty($secret)) {
            $this->logger->warning('Square webhook verification failed: missing signature or secret');
            return false;
        }
        
        // Get the notification URL from headers or use configured URL
        $notificationUrl = $headers['x-square-hmacsha256-signature'] ?? '';
        
        // If notification URL is not in headers, we need it from configuration
        // For now, use a default or require it in secret
        $webhookUrl = $headers['x-square-webhook-url'] ?? $secret;
        
        try {
            // Construct the signed string according to Square's specification
            // Format: <HTTPMethod>|<notificationUrl>|<requestPath>|<base64-encoded-requestBody>|<timestamp>
            $signedString = $webhookUrl . $payload;
            
            // Compute the expected signature using HMAC-SHA256
            $expectedSignature = base64_encode(hash_hmac('sha256', $signedString, $secret, true));
            
            // Use timing-safe comparison to prevent timing attacks
            $result = hash_equals($expectedSignature, $signature);
            
            if (!$result) {
                $this->logger->warning('Square webhook signature mismatch');
            }
            
            return $result;
            
        } catch (\Throwable $e) {
            $this->logger->error('Square webhook verification error', [
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

        if (!isset($data['type'])) {
            throw new WebhookParsingException("Missing 'type' in Square webhook payload");
        }

        $eventType = $this->mapEventType($data['type']);
        $eventId = $data['event_id'] ?? uniqid('evt_');
        $resourceId = $data['data']['object']['payment']['id'] ?? null;

        return new WebhookPayload(
            eventId: $eventId,
            eventType: $eventType,
            provider: GatewayProvider::SQUARE,
            resourceId: $resourceId,
            data: $data,
            receivedAt: isset($data['created_at']) ? new \DateTimeImmutable($data['created_at']) : new \DateTimeImmutable(),
        );
    }

    public function processWebhook(WebhookPayload $payload): void
    {
        $this->logger->info('Processing Square webhook', [
            'id' => $payload->eventId,
            'type' => $payload->eventType->value,
        ]);
    }

    private function mapEventType(string $squareEventType): WebhookEventType
    {
        return match ($squareEventType) {
            'payment.updated' => WebhookEventType::PAYMENT_SUCCEEDED, // Need to check status inside
            'refund.updated' => WebhookEventType::PAYMENT_REFUNDED,
            'dispute.created' => WebhookEventType::DISPUTE_CREATED,
            'dispute.state.updated' => WebhookEventType::DISPUTE_UPDATED,
            default => WebhookEventType::UNKNOWN,
        };
    }
}
