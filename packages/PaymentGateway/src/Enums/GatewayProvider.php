<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Enums;

/**
 * Supported payment gateway providers.
 */
enum GatewayProvider: string
{
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';
    case SQUARE = 'square';
    case ADYEN = 'adyen';
    case BRAINTREE = 'braintree';
    case AUTHORIZE_NET = 'authorize_net';

    /**
     * Get human-readable label for the provider.
     */
    public function label(): string
    {
        return match ($this) {
            self::STRIPE => 'Stripe',
            self::PAYPAL => 'PayPal',
            self::SQUARE => 'Square',
            self::ADYEN => 'Adyen',
            self::BRAINTREE => 'Braintree',
            self::AUTHORIZE_NET => 'Authorize.net',
        };
    }

    /**
     * Check if provider supports 3DS authentication.
     */
    public function supports3DS(): bool
    {
        return match ($this) {
            self::STRIPE, self::ADYEN, self::BRAINTREE => true,
            self::PAYPAL, self::SQUARE, self::AUTHORIZE_NET => false,
        };
    }

    /**
     * Check if provider supports direct card tokenization.
     */
    public function supportsTokenization(): bool
    {
        return match ($this) {
            self::STRIPE, self::PAYPAL, self::SQUARE, self::ADYEN, self::BRAINTREE => true,
            self::AUTHORIZE_NET => false,
        };
    }
}
