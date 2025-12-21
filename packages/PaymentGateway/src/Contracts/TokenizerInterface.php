<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

use Nexus\PaymentGateway\DTOs\TokenizationRequest;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\ValueObjects\PaymentToken;

/**
 * Contract for payment method tokenization.
 *
 * SECURITY: Tokenization should preferably happen client-side using
 * gateway SDKs (Stripe.js, PayPal SDK) to minimize PCI DSS scope.
 * Server-side tokenization requires PCI DSS SAQ D compliance.
 */
interface TokenizerInterface
{
    /**
     * Tokenize a payment method.
     *
     * @throws \Nexus\PaymentGateway\Exceptions\TokenizationFailedException
     */
    public function tokenize(TokenizationRequest $request): PaymentToken;

    /**
     * Retrieve token details.
     *
     * @throws \Nexus\PaymentGateway\Exceptions\TokenNotFoundException
     */
    public function getToken(string $tokenId): PaymentToken;

    /**
     * Delete/invalidate a token.
     *
     * @throws \Nexus\PaymentGateway\Exceptions\GatewayException
     */
    public function deleteToken(string $tokenId): void;

    /**
     * Check if a token is valid and usable.
     */
    public function isTokenValid(string $tokenId): bool;

    /**
     * Get the gateway provider this tokenizer belongs to.
     */
    public function getProvider(): GatewayProvider;
}
