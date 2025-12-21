<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\VirtualCardType;

/**
 * Request DTO for creating a virtual card.
 */
final readonly class VirtualCardRequest
{
    /**
     * @param Money $creditLimit Card credit limit
     * @param VirtualCardType $cardType Type of virtual card
     * @param string|null $vendorId Associated vendor identifier
     * @param string|null $vendorName Vendor name
     * @param string|null $vendorEmail Vendor email for card delivery
     * @param string|null $invoiceReference Related invoice reference
     * @param int $validityDays Number of days card is valid
     * @param array<string> $allowedMerchantCategories Allowed MCC codes
     * @param string|null $merchantLockId Lock to specific merchant ID
     * @param string|null $purchaseOrderReference PO reference
     * @param array<string, string> $metadata Additional metadata
     */
    public function __construct(
        public Money $creditLimit,
        public VirtualCardType $cardType = VirtualCardType::SINGLE_USE,
        public ?string $vendorId = null,
        public ?string $vendorName = null,
        public ?string $vendorEmail = null,
        public ?string $invoiceReference = null,
        public int $validityDays = 30,
        public array $allowedMerchantCategories = [],
        public ?string $merchantLockId = null,
        public ?string $purchaseOrderReference = null,
        public array $metadata = [],
    ) {}

    /**
     * Create a single-use card request.
     */
    public static function singleUse(
        Money $creditLimit,
        ?string $vendorId = null,
        ?string $vendorEmail = null,
        int $validityDays = 30,
    ): self {
        return new self(
            creditLimit: $creditLimit,
            cardType: VirtualCardType::SINGLE_USE,
            vendorId: $vendorId,
            vendorEmail: $vendorEmail,
            validityDays: $validityDays,
        );
    }

    /**
     * Create a multi-use card request.
     */
    public static function multiUse(
        Money $creditLimit,
        ?string $vendorId = null,
        ?string $vendorEmail = null,
        int $validityDays = 365,
    ): self {
        return new self(
            creditLimit: $creditLimit,
            cardType: VirtualCardType::MULTI_USE,
            vendorId: $vendorId,
            vendorEmail: $vendorEmail,
            validityDays: $validityDays,
        );
    }

    /**
     * Create a vendor-locked card request.
     */
    public static function vendorLocked(
        Money $creditLimit,
        string $vendorId,
        string $merchantLockId,
        ?string $vendorEmail = null,
    ): self {
        return new self(
            creditLimit: $creditLimit,
            cardType: VirtualCardType::VENDOR_LOCKED,
            vendorId: $vendorId,
            vendorEmail: $vendorEmail,
            merchantLockId: $merchantLockId,
        );
    }

    /**
     * Get the expiration date based on validity days.
     */
    public function getExpirationDate(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->modify("+{$this->validityDays} days");
    }

    /**
     * Check if the card is vendor-locked.
     */
    public function isVendorLocked(): bool
    {
        return $this->merchantLockId !== null || $this->cardType === VirtualCardType::VENDOR_LOCKED;
    }

    /**
     * Check if MCC restrictions are applied.
     */
    public function hasMccRestrictions(): bool
    {
        return !empty($this->allowedMerchantCategories);
    }

    /**
     * Validate the virtual card request.
     *
     * @return array<string> Validation errors
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->creditLimit->isZero()) {
            $errors[] = 'Credit limit must be greater than zero';
        }

        if ($this->creditLimit->isNegative()) {
            $errors[] = 'Credit limit cannot be negative';
        }

        if ($this->validityDays < 1) {
            $errors[] = 'Validity days must be at least 1';
        }

        if ($this->validityDays > 365) {
            $errors[] = 'Validity days cannot exceed 365';
        }

        if ($this->cardType === VirtualCardType::VENDOR_LOCKED && $this->merchantLockId === null) {
            $errors[] = 'Merchant lock ID is required for vendor-locked cards';
        }

        if ($this->vendorEmail !== null && !filter_var($this->vendorEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid vendor email format';
        }

        return $errors;
    }
}
