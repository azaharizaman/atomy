<?php

declare(strict_types=1);

namespace Nexus\Payment\Entities;

use Nexus\Payment\Contracts\PaymentMethodInterface;
use Nexus\Payment\Enums\PaymentMethodType;

/**
 * Payment method entity.
 *
 * Represents a stored payment method (card, bank account, etc.).
 */
final class PaymentMethod implements PaymentMethodInterface
{
    private bool $isDefault;

    private bool $isActive;

    private bool $isVerified;

    private ?\DateTimeImmutable $lastUsedAt;

    /** @var array<string, mixed> */
    private array $metadata;

    /** @var array<string, mixed>|null */
    private ?array $billingAddress;

    private ?float $maximumAmount;

    private ?float $minimumAmount;

    /**
     * @param array<string, mixed> $metadata
     * @param array<string, mixed>|null $billingAddress
     */
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly string $partyId,
        private readonly PaymentMethodType $type,
        private readonly string $displayLabel,
        private readonly ?string $last4,
        private readonly ?string $fingerprint,
        private readonly ?\DateTimeImmutable $expiresAt,
        private readonly ?string $providerToken,
        private readonly bool $supportsRecurring,
        private readonly \DateTimeImmutable $createdAt,
        bool $isDefault = false,
        bool $isActive = true,
        bool $isVerified = false,
        ?\DateTimeImmutable $lastUsedAt = null,
        array $metadata = [],
        ?array $billingAddress = null,
        ?float $maximumAmount = null,
        ?float $minimumAmount = null,
    ) {
        $this->isDefault = $isDefault;
        $this->isActive = $isActive;
        $this->isVerified = $isVerified;
        $this->lastUsedAt = $lastUsedAt;
        $this->metadata = $metadata;
        $this->billingAddress = $billingAddress;
        $this->maximumAmount = $maximumAmount;
        $this->minimumAmount = $minimumAmount;
    }

    /**
     * Create a new payment method.
     *
     * @param array<string, mixed> $options
     */
    public static function create(
        string $id,
        string $tenantId,
        string $partyId,
        PaymentMethodType $type,
        string $displayLabel,
        array $options = [],
    ): self {
        return new self(
            id: $id,
            tenantId: $tenantId,
            partyId: $partyId,
            type: $type,
            displayLabel: $displayLabel,
            last4: $options['last4'] ?? null,
            fingerprint: $options['fingerprint'] ?? null,
            expiresAt: $options['expires_at'] ?? null,
            providerToken: $options['provider_token'] ?? null,
            supportsRecurring: $options['supports_recurring'] ?? false,
            createdAt: new \DateTimeImmutable(),
            isDefault: $options['is_default'] ?? false,
            isActive: $options['is_active'] ?? true,
            isVerified: $options['is_verified'] ?? false,
            metadata: $options['metadata'] ?? [],
            billingAddress: $options['billing_address'] ?? null,
            maximumAmount: $options['maximum_amount'] ?? null,
            minimumAmount: $options['minimum_amount'] ?? null,
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getPartyId(): string
    {
        return $this->partyId;
    }

    public function getType(): PaymentMethodType
    {
        return $this->type;
    }

    public function getDisplayLabel(): string
    {
        return $this->displayLabel;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function getLast4(): ?string
    {
        return $this->last4;
    }

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getProviderToken(): ?string
    {
        return $this->providerToken;
    }

    public function getProviderReference(): ?string
    {
        return $this->providerToken;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getBillingAddress(): ?array
    {
        return $this->billingAddress;
    }

    public function canBeUsedForAmount(float $amount): bool
    {
        if (!$this->canBeUsed()) {
            return false;
        }

        if ($this->minimumAmount !== null && $amount < $this->minimumAmount) {
            return false;
        }

        if ($this->maximumAmount !== null && $amount > $this->maximumAmount) {
            return false;
        }

        return true;
    }

    public function supportsRecurring(): bool
    {
        return $this->supportsRecurring;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Set this payment method as the default.
     */
    public function setAsDefault(): void
    {
        $this->isDefault = true;
    }

    /**
     * Clear the default flag.
     */
    public function clearDefault(): void
    {
        $this->isDefault = false;
    }

    /**
     * Activate the payment method.
     */
    public function activate(): void
    {
        $this->isActive = true;
    }

    /**
     * Deactivate the payment method.
     */
    public function deactivate(): void
    {
        $this->isActive = false;
    }

    /**
     * Mark the payment method as verified.
     */
    public function verify(): void
    {
        $this->isVerified = true;
    }

    /**
     * Update the last used timestamp.
     */
    public function updateLastUsed(\DateTimeImmutable $usedAt): void
    {
        $this->lastUsedAt = $usedAt;
    }

    /**
     * Add metadata to the payment method.
     *
     * @param array<string, mixed> $metadata
     */
    public function addMetadata(array $metadata): void
    {
        $this->metadata = array_merge($this->metadata, $metadata);
    }

    /**
     * Check if this method can be used for payment.
     */
    public function canBeUsed(): bool
    {
        return $this->isActive && !$this->isExpired();
    }
}
