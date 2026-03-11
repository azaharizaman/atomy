<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Webhooks;

use Nexus\PaymentGateway\Contracts\WebhookHandlerInterface;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\WebhookEventType;
use Nexus\PaymentGateway\Exceptions\WebhookParsingException;
use Nexus\PaymentGateway\ValueObjects\WebhookPayload;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Handles Adyen webhooks.
 *
 * @see https://docs.adyen.com/development-resources/webhooks
 */
final class AdyenWebhookHandler implements WebhookHandlerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getProvider(): GatewayProvider
    {
        return GatewayProvider::ADYEN;
    }

    public function verifySignature(
        string $payload,
        string $signature,
        string $secret,
        array $headers = []
    ): bool {
        if (empty($secret)) {
            $this->logger->warning('Adyen webhook verification failed: missing secret');
            return false;
        }

        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                $this->logger->warning('Adyen webhook verification failed: invalid payload');
                return false;
            }

            $item = $this->extractNotificationRequestItem($data);
            if ($item === null) {
                $this->logger->warning('Adyen webhook verification failed: invalid notification structure');
                return false;
            }

            $embeddedSignature = $item['additionalData']['hmacSignature'] ?? '';
            $receivedSignature = $signature !== '' ? $signature : (string) $embeddedSignature;
            if ($receivedSignature === '') {
                $this->logger->warning('Adyen webhook verification failed: missing signature');
                return false;
            }

            $expectedSignature = $this->computeExpectedSignature($item, $secret);
            if ($expectedSignature === null) {
                $this->logger->warning('Adyen webhook verification failed: missing required signing fields');
                return false;
            }

            $result = hash_equals($expectedSignature, $receivedSignature);
            if (!$result) {
                $this->logger->warning('Adyen webhook signature mismatch');
            }

            return $result;
        } catch (\JsonException $e) {
            $this->logger->warning('Adyen webhook verification failed: invalid JSON payload');
            return false;
        } catch (\Throwable $e) {
            $this->logger->error('Adyen webhook verification error', [
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

        $item = $this->extractNotificationRequestItem($data);
        if ($item === null) {
            throw new WebhookParsingException("Invalid Adyen webhook structure");
        }

        $eventCode = (string) ($item['eventCode'] ?? '');
        
        $normalizedSuccess = $this->normalizeSuccessValue($item['success'] ?? 'false');
        $eventType = $this->mapEventType($eventCode, $normalizedSuccess);
        $eventId = $this->buildEventId($item);
        $resourceId = $item['pspReference'] ?? null;
        $receivedAt = new \DateTimeImmutable();
        if (isset($item['eventDate']) && is_string($item['eventDate'])) {
            try {
                $receivedAt = new \DateTimeImmutable($item['eventDate']);
            } catch (\Exception) {
                // Keep fallback to current timestamp when provider sends invalid date.
            }
        }

        return new WebhookPayload(
            eventId: $eventId,
            eventType: $eventType,
            provider: GatewayProvider::ADYEN,
            resourceId: $resourceId,
            data: $data,
            receivedAt: $receivedAt,
        );
    }

    public function processWebhook(WebhookPayload $payload): void
    {
        $this->logger->info('Processing Adyen webhook', [
            'id' => $payload->eventId,
            'type' => $payload->eventType->value,
        ]);
    }

    private function mapEventType(string $eventCode, string $success): WebhookEventType
    {
        if (strtolower($success) !== 'true') {
            return WebhookEventType::PAYMENT_FAILED;
        }

        return match ($eventCode) {
            'AUTHORISATION' => WebhookEventType::PAYMENT_AUTHORIZED,
            'CAPTURE' => WebhookEventType::PAYMENT_CAPTURED,
            'REFUND' => WebhookEventType::REFUND_COMPLETED,
            'CHARGEBACK' => WebhookEventType::DISPUTE_CREATED,
            'CHARGEBACK_REVERSED' => WebhookEventType::DISPUTE_WON,
            default => WebhookEventType::UNKNOWN,
        };
    }

    /**
     * @param array<mixed> $payload
     * @return array<string, mixed>|null
     */
    private function extractNotificationRequestItem(array $payload): ?array
    {
        $item = $payload['notificationItems'][0]['NotificationRequestItem'] ?? null;
        if (!is_array($item)) {
            return null;
        }

        return $item;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function buildEventId(array $item): string
    {
        $pspReference = (string) ($item['pspReference'] ?? '');
        $eventCode = (string) ($item['eventCode'] ?? 'unknown');
        $success = $this->normalizeSuccessValue($item['success'] ?? 'false');

        if ($pspReference === '') {
            throw new WebhookParsingException("Missing 'pspReference' in Adyen webhook payload");
        }

        return 'ady_' . hash('sha256', $pspReference . '|' . $eventCode . '|' . $success);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function computeExpectedSignature(array $item, string $secret): ?string
    {
        $amount = $item['amount'] ?? null;
        if (!is_array($amount)) {
            return null;
        }

        $value = [
            (string) ($item['pspReference'] ?? ''),
            (string) ($item['originalReference'] ?? ''),
            (string) ($item['merchantAccountCode'] ?? ''),
            (string) ($item['merchantReference'] ?? ''),
            (string) ($amount['value'] ?? ''),
            (string) ($amount['currency'] ?? ''),
            (string) ($item['eventCode'] ?? ''),
            $this->normalizeSuccessValue($item['success'] ?? ''),
        ];

        if ($value[0] === '' || $value[2] === '' || $value[4] === '' || $value[5] === '' || $value[6] === '' || $value[7] === '') {
            return null;
        }

        $escaped = array_map(static fn (string $part): string => str_replace(['\\', ':'], ['\\\\', '\\:'], $part), $value);
        $signingData = implode(':', $escaped);

        $decodedSecret = base64_decode($secret, true);
        $hmacKey = $decodedSecret === false ? $secret : $decodedSecret;

        return base64_encode(hash_hmac('sha256', $signingData, $hmacKey, true));
    }

    private function normalizeSuccessValue(mixed $success): string
    {
        if (is_bool($success)) {
            return $success ? 'true' : 'false';
        }

        $normalized = strtolower(trim((string) $success));

        return in_array($normalized, ['true', '1', 'yes'], true) ? 'true' : 'false';
    }
}
