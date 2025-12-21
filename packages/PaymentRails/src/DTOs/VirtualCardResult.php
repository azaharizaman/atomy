<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\VirtualCardStatus;
use Nexus\PaymentRails\Enums\VirtualCardType;
use Nexus\PaymentRails\ValueObjects\VirtualCard;

/**
 * Result DTO for virtual card operations.
 */
final readonly class VirtualCardResult
{
    /**
     * @param string $cardId Unique card identifier
     * @param bool $success Whether the operation was successful
     * @param VirtualCardStatus $status Card status
     * @param VirtualCardType $cardType Type of virtual card
     * @param Money $creditLimit Card credit limit
     * @param Money $availableCredit Available credit
     * @param string|null $maskedCardNumber Masked card number
     * @param string|null $expirationDate Formatted expiration date
     * @param string|null $vendorId Associated vendor
     * @param array<string> $errors Any errors encountered
     * @param \DateTimeImmutable $createdAt Creation timestamp
     * @param \DateTimeImmutable|null $expiresAt Expiration timestamp
     * @param bool $cardDataIncluded Whether full card data is included
     * @param string|null $fullCardNumber Full card number (only for initial creation)
     * @param string|null $cvv CVV (only for initial creation)
     */
    public function __construct(
        public string $cardId,
        public bool $success,
        public VirtualCardStatus $status,
        public VirtualCardType $cardType,
        public Money $creditLimit,
        public Money $availableCredit,
        public ?string $maskedCardNumber = null,
        public ?string $expirationDate = null,
        public ?string $vendorId = null,
        public array $errors = [],
        public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        public ?\DateTimeImmutable $expiresAt = null,
        public bool $cardDataIncluded = false,
        public ?string $fullCardNumber = null,
        public ?string $cvv = null,
    ) {}

    /**
     * Create a successful result from a virtual card.
     *
     * @param bool $includeCardData Whether to include full card number and CVV
     */
    public static function success(VirtualCard $card, bool $includeCardData = false): self
    {
        return new self(
            cardId: $card->id,
            success: true,
            status: $card->status,
            cardType: $card->cardType,
            creditLimit: $card->creditLimit,
            availableCredit: $card->availableCredit,
            maskedCardNumber: $card->getMaskedCardNumber(),
            expirationDate: $card->getExpirationFormatted(),
            vendorId: $card->vendorId,
            createdAt: $card->createdAt,
            expiresAt: $card->expiresAt,
            cardDataIncluded: $includeCardData,
            fullCardNumber: $includeCardData ? $card->cardNumber->toString() : null,
            cvv: $includeCardData ? $card->cvv : null,
        );
    }

    /**
     * Create a failure result.
     *
     * @param array<string> $errors
     */
    public static function failure(
        string $cardId,
        Money $creditLimit,
        array $errors,
    ): self {
        return new self(
            cardId: $cardId,
            success: false,
            status: VirtualCardStatus::CANCELLED,
            cardType: VirtualCardType::SINGLE_USE,
            creditLimit: $creditLimit,
            availableCredit: Money::zero($creditLimit->currency),
            errors: $errors,
        );
    }

    /**
     * Get the utilization percentage.
     */
    public function getUtilizationPercentage(): float
    {
        if ($this->creditLimit->isZero()) {
            return 0.0;
        }

        $used = $this->creditLimit->subtract($this->availableCredit)->getAmountAsFloat();
        $limit = $this->creditLimit->getAmountAsFloat();

        return round(($used / $limit) * 100, 2);
    }

    /**
     * Check if the card is active.
     */
    public function isActive(): bool
    {
        return $this->status === VirtualCardStatus::ACTIVE;
    }

    /**
     * Check if there is available credit.
     */
    public function hasAvailableCredit(): bool
    {
        return $this->availableCredit->isPositive();
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get the card data for the vendor.
     *
     * @return array{card_number: string|null, cvv: string|null, expiration: string|null}|null
     */
    public function getCardDataForVendor(): ?array
    {
        if (!$this->cardDataIncluded) {
            return null;
        }

        return [
            'card_number' => $this->fullCardNumber,
            'cvv' => $this->cvv,
            'expiration' => $this->expirationDate,
        ];
    }
}
