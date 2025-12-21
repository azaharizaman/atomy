<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\ValueObjects;

use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Exceptions\InvalidCredentialsException;

/**
 * Gateway API credentials (API keys, secrets, etc.).
 *
 * SECURITY: Never log or expose these values.
 */
final class GatewayCredentials
{
    /**
     * @param GatewayProvider $provider Gateway provider
     * @param string $apiKey Public/secret API key
     * @param string|null $apiSecret API secret (if separate)
     * @param string|null $merchantId Merchant account ID
     * @param string|null $webhookSecret Secret for webhook signature verification
     * @param bool $sandboxMode Whether using sandbox/test environment
     * @param array<string, mixed> $additionalConfig Gateway-specific configuration
     */
    public function __construct(
        public readonly GatewayProvider $provider,
        public readonly string $apiKey,
        public readonly ?string $apiSecret = null,
        public readonly ?string $merchantId = null,
        public readonly ?string $webhookSecret = null,
        public readonly bool $sandboxMode = false,
        public readonly array $additionalConfig = [],
    ) {
        if (trim($apiKey) === '') {
            throw new InvalidCredentialsException('API key cannot be empty');
        }
    }

    /**
     * Create credentials for Stripe.
     */
    public static function forStripe(
        string $secretKey,
        ?string $webhookSecret = null,
        bool $sandboxMode = false,
    ): self {
        return new self(
            provider: GatewayProvider::STRIPE,
            apiKey: $secretKey,
            webhookSecret: $webhookSecret,
            sandboxMode: $sandboxMode,
        );
    }

    /**
     * Create credentials for PayPal.
     */
    public static function forPayPal(
        string $clientId,
        string $clientSecret,
        ?string $webhookId = null,
        bool $sandboxMode = false,
    ): self {
        return new self(
            provider: GatewayProvider::PAYPAL,
            apiKey: $clientId,
            apiSecret: $clientSecret,
            webhookSecret: $webhookId,
            sandboxMode: $sandboxMode,
        );
    }

    /**
     * Create credentials for Square.
     */
    public static function forSquare(
        string $accessToken,
        string $locationId,
        ?string $webhookSignatureKey = null,
        bool $sandboxMode = false,
    ): self {
        return new self(
            provider: GatewayProvider::SQUARE,
            apiKey: $accessToken,
            merchantId: $locationId,
            webhookSecret: $webhookSignatureKey,
            sandboxMode: $sandboxMode,
        );
    }

    /**
     * Create credentials for Adyen.
     */
    public static function forAdyen(
        string $apiKey,
        string $merchantAccount,
        ?string $hmacKey = null,
        bool $sandboxMode = false,
    ): self {
        return new self(
            provider: GatewayProvider::ADYEN,
            apiKey: $apiKey,
            merchantId: $merchantAccount,
            webhookSecret: $hmacKey,
            sandboxMode: $sandboxMode,
        );
    }

    /**
     * Check if webhook verification is possible.
     */
    public function canVerifyWebhooks(): bool
    {
        return $this->webhookSecret !== null && $this->webhookSecret !== '';
    }

    /**
     * Get environment name.
     */
    public function getEnvironment(): string
    {
        return $this->sandboxMode ? 'sandbox' : 'production';
    }

    /**
     * Get a configuration value.
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->additionalConfig[$key] ?? $default;
    }
}
