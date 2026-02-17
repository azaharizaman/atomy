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
     * Create credentials from configuration array.
     *
     * @param array<string, mixed> $config Configuration array
     * @return self
     * @throws InvalidCredentialsException If provider is invalid or missing
     */
    public static function fromArray(array $config): self
    {
        $provider = $config['provider'] ?? null;
        
        if (!$provider instanceof GatewayProvider) {
            if (is_string($provider)) {
                try {
                    $provider = GatewayProvider::from($provider);
                } catch (\ValueError $e) {
                    $validProvidersLines = array_map(
                        static fn (GatewayProvider $case): string => ' - ' . $case->value,
                        GatewayProvider::cases()
                    );
                    $validProvidersMessage = implode(PHP_EOL, $validProvidersLines);

                    throw new InvalidCredentialsException(
                        "Invalid provider '{$provider}'. Valid providers are:" . PHP_EOL . $validProvidersMessage
                    );
                }
            } else {
                throw new InvalidCredentialsException('Provider is required and must be a string or GatewayProvider enum');
            }
        }
        
        // Validate that only one naming convention is used per field to prevent ambiguity
        self::validateKeyFormat($config, 'apiKey', 'api_key');
        self::validateKeyFormat($config, 'apiSecret', 'api_secret');
        self::validateKeyFormat($config, 'merchantId', 'merchant_id');
        self::validateKeyFormat($config, 'webhookSecret', 'webhook_secret');
        self::validateKeyFormat($config, 'sandboxMode', 'sandbox_mode');
        self::validateKeyFormat($config, 'additionalConfig', 'additional_config');
        
        return new self(
            provider: $provider,
            apiKey: $config['apiKey'] ?? $config['api_key'] ?? '',
            apiSecret: $config['apiSecret'] ?? $config['api_secret'] ?? null,
            merchantId: $config['merchantId'] ?? $config['merchant_id'] ?? null,
            webhookSecret: $config['webhookSecret'] ?? $config['webhook_secret'] ?? null,
            sandboxMode: $config['sandboxMode'] ?? $config['sandbox_mode'] ?? false,
            additionalConfig: $config['additionalConfig'] ?? $config['additional_config'] ?? [],
        );
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

    /**
     * Validate that only one naming convention is used for a configuration key.
     *
     * @param array<string, mixed> $config The configuration array
     * @param string $camelCaseKey The camelCase key name
     * @param string $snakeCaseKey The snake_case key name
     * @throws InvalidCredentialsException If both keys are present
     */
    private static function validateKeyFormat(array $config, string $camelCaseKey, string $snakeCaseKey): void
    {
        if (array_key_exists($camelCaseKey, $config) && array_key_exists($snakeCaseKey, $config)) {
            throw new InvalidCredentialsException(
                "Ambiguous configuration: both '{$camelCaseKey}' and '{$snakeCaseKey}' are present. Use only one naming convention."
            );
        }
    }
}