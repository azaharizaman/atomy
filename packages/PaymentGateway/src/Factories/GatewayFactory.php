<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Factories;

use Nexus\Connector\Contracts\HttpClientInterface;
use Nexus\PaymentGateway\Contracts\GatewayFactoryInterface;
use Nexus\PaymentGateway\Contracts\GatewayInterface;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Gateways\AdyenGateway;
use Nexus\PaymentGateway\Gateways\PayPalGateway;
use Nexus\PaymentGateway\Gateways\SquareGateway;
use Nexus\PaymentGateway\Gateways\BraintreeGateway;
use Nexus\PaymentGateway\Gateways\AuthorizeNetGateway;
use Nexus\PaymentGateway\Gateways\StripeGateway;
use Nexus\PaymentGateway\ValueObjects\GatewayCredentials;

final class GatewayFactory implements GatewayFactoryInterface
{
    /**
     * List of supported gateway providers.
     * This is the single source of truth for available gateways.
     * 
     * @var array<GatewayProvider>
     */
    private const SUPPORTED_GATEWAYS = [
        GatewayProvider::STRIPE,
        GatewayProvider::PAYPAL,
        GatewayProvider::SQUARE,
        GatewayProvider::BRAINTREE,
        GatewayProvider::AUTHORIZE_NET,
        GatewayProvider::ADYEN,
    ];
    
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {}

    public function create(GatewayProvider $provider, array $config): GatewayInterface
    {
        // Convert config array to GatewayCredentials if provided
        $credentials = !empty($config) ? GatewayCredentials::fromArray($config) : null;
        
        $gateway = match ($provider) {
            GatewayProvider::STRIPE => new StripeGateway($this->httpClient, $credentials),
            GatewayProvider::PAYPAL => new PayPalGateway($this->httpClient, $credentials),
            GatewayProvider::SQUARE => new SquareGateway($this->httpClient, $credentials),
            GatewayProvider::BRAINTREE => new BraintreeGateway($this->httpClient, $credentials),
            GatewayProvider::AUTHORIZE_NET => new AuthorizeNetGateway($this->httpClient, $credentials),
            GatewayProvider::ADYEN => new AdyenGateway($this->httpClient, $credentials),
            default => throw new \InvalidArgumentException("Gateway provider {$provider->value} is not supported yet."),
        };

        return $gateway;
    }
    
    public function supports(GatewayProvider $provider): bool
    {
        return in_array($provider, self::SUPPORTED_GATEWAYS, true);
    }
    
    /**
     * @return array<GatewayProvider>
     */
    public function getAvailableGateways(): array
    {
        return self::SUPPORTED_GATEWAYS;
    }
}
