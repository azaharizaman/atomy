<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Payment\Enums\PaymentMethodType;

/**
 * Contract for payment method entities.
 *
 * Represents a stored payment method that can be used for transactions.
 */
interface PaymentMethodInterface
{
    /**
     * Get the unique method ID.
     */
    public function getId(): string;

    /**
     * Get the tenant ID this method belongs to.
     */
    public function getTenantId(): string;

    /**
     * Get the party/customer ID this method is linked to.
     */
    public function getPartyId(): string;

    /**
     * Get the payment method type.
     */
    public function getType(): PaymentMethodType;

    /**
     * Get a display-safe label for this method.
     * E.g., "Visa ending in 4242" or "Bank account ending in 1234"
     */
    public function getDisplayLabel(): string;

    /**
     * Check if this method is the default for its type.
     */
    public function isDefault(): bool;

    /**
     * Check if this method is currently active/enabled.
     */
    public function isActive(): bool;

    /**
     * Check if this method has been verified.
     */
    public function isVerified(): bool;

    /**
     * Get the expiration date if applicable.
     */
    public function getExpiresAt(): ?\DateTimeImmutable;

    /**
     * Check if this method has expired.
     */
    public function isExpired(): bool;

    /**
     * Get the last 4 digits/characters of the account/card number.
     */
    public function getLast4(): ?string;

    /**
     * Get the fingerprint/hash for duplicate detection.
     */
    public function getFingerprint(): ?string;

    /**
     * Get the date/time when this method was created.
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get the date/time when this method was last used.
     */
    public function getLastUsedAt(): ?\DateTimeImmutable;

    /**
     * Get the external provider's reference for this method.
     */
    public function getProviderReference(): ?string;

    /**
     * Get billing address information.
     *
     * @return array<string, mixed>|null
     */
    public function getBillingAddress(): ?array;

    /**
     * Get additional metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Check if this method can be used for the given amount.
     */
    public function canBeUsedForAmount(float $amount): bool;

    /**
     * Check if this method supports recurring payments.
     */
    public function supportsRecurring(): bool;
}
