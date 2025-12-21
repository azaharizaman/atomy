<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\ValueObjects;

use Nexus\PaymentGateway\Enums\CardBrand;
use Nexus\PaymentGateway\Enums\GatewayProvider;

/**
 * Tokenized payment method (card token from gateway).
 *
 * SECURITY: Only stores gateway-provided token, never raw card data.
 */
final class PaymentToken
{
    /**
     * @param string $tokenId Gateway-provided token identifier
     * @param GatewayProvider $provider Gateway that issued the token
     * @param CardMetadata|null $cardMetadata Associated card metadata (last4, brand, etc.)
     * @param string|null $customerId Gateway customer ID (if linked)
     * @param \DateTimeImmutable|null $expiresAt Token expiration time (if applicable)
     * @param \DateTimeImmutable $createdAt When the token was created
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public readonly string $tokenId,
        public readonly GatewayProvider $provider,
        public readonly ?CardMetadata $cardMetadata = null,
        public readonly ?string $customerId = null,
        public readonly ?\DateTimeImmutable $expiresAt = null,
        public readonly \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        public readonly array $metadata = [],
    ) {}

    /**
     * Check if token is expired.
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt < new \DateTimeImmutable();
    }

    /**
     * Check if token is linked to a gateway customer.
     */
    public function isLinkedToCustomer(): bool
    {
        return $this->customerId !== null;
    }

    /**
     * Get display label for the token.
     */
    public function getDisplayLabel(): string
    {
        if ($this->cardMetadata !== null) {
            return sprintf(
                '%s •••• %s',
                $this->cardMetadata->brand->label(),
                $this->cardMetadata->lastFour,
            );
        }

        return sprintf('Token %s', substr($this->tokenId, 0, 8));
    }

    /**
     * Get card brand (if available).
     */
    public function getCardBrand(): ?CardBrand
    {
        return $this->cardMetadata?->brand;
    }

    /**
     * Get last four digits (if available).
     */
    public function getLastFour(): ?string
    {
        return $this->cardMetadata?->lastFour;
    }
}
