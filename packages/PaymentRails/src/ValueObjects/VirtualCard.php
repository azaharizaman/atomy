<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\VirtualCardStatus;
use Nexus\PaymentRails\Enums\VirtualCardType;

/**
 * Represents a virtual card for payments.
 *
 * Virtual cards are single-use or limited-use payment instruments
 * generated programmatically for B2B payments.
 */
final class VirtualCard
{
    /**
     * @param string $id Unique identifier
     * @param VirtualCardNumber $cardNumber The card number
     * @param string $expirationMonth Expiration month (01-12)
     * @param string $expirationYear Expiration year (4-digit)
     * @param string $cvv Card verification value
     * @param VirtualCardType $cardType Type of virtual card
     * @param VirtualCardStatus $status Current status
     * @param Money $creditLimit Credit limit/authorized amount
     * @param Money $availableCredit Remaining available credit
     * @param string|null $vendorId Associated vendor
     * @param string|null $vendorName Vendor name
     * @param string|null $invoiceReference Invoice reference
     * @param \DateTimeImmutable $createdAt Creation timestamp
     * @param \DateTimeImmutable|null $activatedAt Activation timestamp
     * @param \DateTimeImmutable|null $expiresAt Expiration timestamp
     * @param \DateTimeImmutable|null $closedAt Closure timestamp
     * @param array<string> $allowedMerchantCategories MCC restrictions
     * @param string|null $merchantLock Locked to specific merchant ID
     */
    public function __construct(
        public readonly string $id,
        public readonly VirtualCardNumber $cardNumber,
        public readonly string $expirationMonth,
        public readonly string $expirationYear,
        public readonly string $cvv,
        public readonly VirtualCardType $cardType,
        public readonly VirtualCardStatus $status,
        public readonly Money $creditLimit,
        public readonly Money $availableCredit,
        public readonly ?string $vendorId = null,
        public readonly ?string $vendorName = null,
        public readonly ?string $invoiceReference = null,
        public readonly \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        public readonly ?\DateTimeImmutable $activatedAt = null,
        public readonly ?\DateTimeImmutable $expiresAt = null,
        public readonly ?\DateTimeImmutable $closedAt = null,
        public readonly array $allowedMerchantCategories = [],
        public readonly ?string $merchantLock = null,
    ) {}

    /**
     * Create a new single-use virtual card.
     */
    public static function singleUse(
        string $id,
        VirtualCardNumber $cardNumber,
        string $expirationMonth,
        string $expirationYear,
        string $cvv,
        Money $creditLimit,
        ?string $vendorId = null,
        ?\DateTimeImmutable $expiresAt = null,
    ): self {
        return new self(
            id: $id,
            cardNumber: $cardNumber,
            expirationMonth: $expirationMonth,
            expirationYear: $expirationYear,
            cvv: $cvv,
            cardType: VirtualCardType::SINGLE_USE,
            status: VirtualCardStatus::ACTIVE,
            creditLimit: $creditLimit,
            availableCredit: $creditLimit,
            vendorId: $vendorId,
            expiresAt: $expiresAt ?? (new \DateTimeImmutable())->modify('+30 days'),
            activatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create a new multi-use virtual card.
     */
    public static function multiUse(
        string $id,
        VirtualCardNumber $cardNumber,
        string $expirationMonth,
        string $expirationYear,
        string $cvv,
        Money $creditLimit,
        ?string $vendorId = null,
        ?\DateTimeImmutable $expiresAt = null,
    ): self {
        return new self(
            id: $id,
            cardNumber: $cardNumber,
            expirationMonth: $expirationMonth,
            expirationYear: $expirationYear,
            cvv: $cvv,
            cardType: VirtualCardType::MULTI_USE,
            status: VirtualCardStatus::ACTIVE,
            creditLimit: $creditLimit,
            availableCredit: $creditLimit,
            vendorId: $vendorId,
            expiresAt: $expiresAt ?? (new \DateTimeImmutable())->modify('+365 days'),
            activatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create a vendor-locked card.
     */
    public static function vendorLocked(
        string $id,
        VirtualCardNumber $cardNumber,
        string $expirationMonth,
        string $expirationYear,
        string $cvv,
        Money $creditLimit,
        string $vendorId,
        string $merchantLockId,
    ): self {
        return new self(
            id: $id,
            cardNumber: $cardNumber,
            expirationMonth: $expirationMonth,
            expirationYear: $expirationYear,
            cvv: $cvv,
            cardType: VirtualCardType::VENDOR_LOCKED,
            status: VirtualCardStatus::ACTIVE,
            creditLimit: $creditLimit,
            availableCredit: $creditLimit,
            vendorId: $vendorId,
            activatedAt: new \DateTimeImmutable(),
            merchantLock: $merchantLockId,
        );
    }

    /**
     * Record a charge against the card.
     */
    public function recordCharge(Money $amount): self
    {
        if (!$this->canBeCharged($amount)) {
            throw new \LogicException('Cannot charge this amount to the card');
        }

        return new self(
            id: $this->id,
            cardNumber: $this->cardNumber,
            expirationMonth: $this->expirationMonth,
            expirationYear: $this->expirationYear,
            cvv: $this->cvv,
            cardType: $this->cardType,
            status: $this->status,
            creditLimit: $this->creditLimit,
            availableCredit: $this->availableCredit->subtract($amount),
            vendorId: $this->vendorId,
            vendorName: $this->vendorName,
            invoiceReference: $this->invoiceReference,
            createdAt: $this->createdAt,
            activatedAt: $this->activatedAt,
            expiresAt: $this->expiresAt,
            closedAt: $this->closedAt,
            allowedMerchantCategories: $this->allowedMerchantCategories,
            merchantLock: $this->merchantLock,
        );
    }

    /**
     * Close the card.
     */
    public function close(): self
    {
        if ($this->status === VirtualCardStatus::CLOSED) {
            throw new \LogicException('Card is already closed');
        }

        return new self(
            id: $this->id,
            cardNumber: $this->cardNumber,
            expirationMonth: $this->expirationMonth,
            expirationYear: $this->expirationYear,
            cvv: $this->cvv,
            cardType: $this->cardType,
            status: VirtualCardStatus::CLOSED,
            creditLimit: $this->creditLimit,
            availableCredit: $this->availableCredit,
            vendorId: $this->vendorId,
            vendorName: $this->vendorName,
            invoiceReference: $this->invoiceReference,
            createdAt: $this->createdAt,
            activatedAt: $this->activatedAt,
            expiresAt: $this->expiresAt,
            closedAt: new \DateTimeImmutable(),
            allowedMerchantCategories: $this->allowedMerchantCategories,
            merchantLock: $this->merchantLock,
        );
    }

    /**
     * Suspend the card.
     */
    public function suspend(): self
    {
        if ($this->status !== VirtualCardStatus::ACTIVE) {
            throw new \LogicException('Can only suspend active cards');
        }

        return new self(
            id: $this->id,
            cardNumber: $this->cardNumber,
            expirationMonth: $this->expirationMonth,
            expirationYear: $this->expirationYear,
            cvv: $this->cvv,
            cardType: $this->cardType,
            status: VirtualCardStatus::SUSPENDED,
            creditLimit: $this->creditLimit,
            availableCredit: $this->availableCredit,
            vendorId: $this->vendorId,
            vendorName: $this->vendorName,
            invoiceReference: $this->invoiceReference,
            createdAt: $this->createdAt,
            activatedAt: $this->activatedAt,
            expiresAt: $this->expiresAt,
            closedAt: $this->closedAt,
            allowedMerchantCategories: $this->allowedMerchantCategories,
            merchantLock: $this->merchantLock,
        );
    }

    /**
     * Reactivate a suspended card.
     */
    public function reactivate(): self
    {
        if ($this->status !== VirtualCardStatus::SUSPENDED) {
            throw new \LogicException('Can only reactivate suspended cards');
        }

        return new self(
            id: $this->id,
            cardNumber: $this->cardNumber,
            expirationMonth: $this->expirationMonth,
            expirationYear: $this->expirationYear,
            cvv: $this->cvv,
            cardType: $this->cardType,
            status: VirtualCardStatus::ACTIVE,
            creditLimit: $this->creditLimit,
            availableCredit: $this->availableCredit,
            vendorId: $this->vendorId,
            vendorName: $this->vendorName,
            invoiceReference: $this->invoiceReference,
            createdAt: $this->createdAt,
            activatedAt: $this->activatedAt,
            expiresAt: $this->expiresAt,
            closedAt: $this->closedAt,
            allowedMerchantCategories: $this->allowedMerchantCategories,
            merchantLock: $this->merchantLock,
        );
    }

    /**
     * Check if the card can be charged the given amount.
     */
    public function canBeCharged(Money $amount): bool
    {
        if ($this->status !== VirtualCardStatus::ACTIVE) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if ($amount->greaterThan($this->availableCredit)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the card is expired.
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt !== null) {
            return new \DateTimeImmutable() > $this->expiresAt;
        }

        // Check card expiration date
        $expiration = \DateTimeImmutable::createFromFormat(
            'Y-m-d',
            sprintf('%s-%s-01', $this->expirationYear, $this->expirationMonth)
        );

        if ($expiration === false) {
            return true;
        }

        // Card expires at end of the expiration month
        $expiration = $expiration->modify('last day of this month 23:59:59');

        return new \DateTimeImmutable() > $expiration;
    }

    /**
     * Check if the card has available credit.
     */
    public function hasAvailableCredit(): bool
    {
        return $this->availableCredit->isPositive();
    }

    /**
     * Get the used amount.
     */
    public function getUsedAmount(): Money
    {
        return $this->creditLimit->subtract($this->availableCredit);
    }

    /**
     * Get utilization percentage.
     */
    public function getUtilizationPercentage(): float
    {
        if ($this->creditLimit->isZero()) {
            return 0.0;
        }

        $used = $this->getUsedAmount()->getAmount();
        $limit = $this->creditLimit->getAmount();

        return round(($used / $limit) * 100, 2);
    }

    /**
     * Get the formatted expiration date.
     */
    public function getExpirationFormatted(): string
    {
        return sprintf('%s/%s', $this->expirationMonth, mb_substr($this->expirationYear, -2));
    }

    /**
     * Get the card number for display.
     */
    public function getMaskedCardNumber(): string
    {
        return $this->cardNumber->masked();
    }

    /**
     * Check if the card is single use.
     */
    public function isSingleUse(): bool
    {
        return $this->cardType === VirtualCardType::SINGLE_USE;
    }

    /**
     * Check if the card is vendor locked.
     */
    public function isVendorLocked(): bool
    {
        return $this->merchantLock !== null;
    }
}
