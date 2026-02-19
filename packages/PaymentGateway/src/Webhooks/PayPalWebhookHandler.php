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
     * 2. Fetch the certificate from paypal-cert-url header
     * 3. Reconstruct the signed string according to PayPal's specification
     * 4. Verify the signature using the certificate's public key
     * 5. Return true only if verification succeeds
     *
     * @see https://developer.paypal.com/api/rest/webhooks/#verify-webhook-signature
     * 
     * @throws \RuntimeException When certificate cannot be fetched or verification fails
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
        
        // Validate certificate URL is from PayPal (security requirement)
        if (!str_starts_with($certUrl, 'https://api.paypal.com') && 
            !str_starts_with($certUrl, 'https://api.sandbox.paypal.com')) {
            $this->logger->warning('PayPal webhook verification failed: invalid cert URL');
            return false;
        }
        
        try {
            // Fetch the certificate from PayPal's cert URL
            $certContent = $this->fetchCertificate($certUrl);
            
            if (empty($certContent)) {
                $this->logger->error('PayPal webhook verification failed: could not fetch certificate');
                return false;
            }
            
            // Extract public key from certificate
            $publicKey = $this->extractPublicKeyFromCert($certContent);
            
            if ($publicKey === false) {
                $this->logger->error('PayPal webhook verification failed: could not extract public key');
                return false;
            }
            
            // Construct the signed string according to PayPal's specification
            // Format: <transmissionId>|<transmissionTime>|<webhookId>|<crc32>
            $crc = crc32($payload);
            $signedString = $transmissionId . '|' . $transmissionTime . '|' . $secret . '|' . $crc;
            
            // Verify the signature using OpenSSL with the certificate's public key
            $result = $this->verifyWithPublicKey($signedString, $signature, $publicKey);
            
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
    
    /**
     * Fetch certificate from URL.
     */
    private function fetchCertificate(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        return $response ?: '';
    }
    
    /**
     * Extract public key from PEM certificate.
     */
    private function extractPublicKeyFromCert(string $certContent)
    {
        $cert = openssl_x509_read($certContent);
        
        if ($cert === false) {
            return false;
        }
        
        $publicKey = openssl_pkey_get_public($cert);
        openssl_x509_free($cert);
        
        return $publicKey;
    }
    
    /**
     * Verify signature using public key.
     */
    private function verifyWithPublicKey(string $data, string $signature, $publicKey): bool
    {
        // Decode base64 signature
        $decodedSignature = base64_decode($signature, true);
        
        if ($decodedSignature === false) {
            return false;
        }
        
        // Verify using SHA-256 with RSA
        $result = openssl_verify(
            $data,
            $decodedSignature,
            $publicKey,
            OPENSSL_ALGO_SHA256
        );
        
        return $result === 1;
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
