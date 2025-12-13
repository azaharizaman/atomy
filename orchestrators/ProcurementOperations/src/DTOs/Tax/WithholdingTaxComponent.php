<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Tax;

use Nexus\Common\ValueObjects\Money;

/**
 * A single component of withholding tax.
 */
final readonly class WithholdingTaxComponent
{
    public function __construct(
        public string $code,
        public string $description,
        public float $rate,
        public Money $amount,
        public ?string $taxAuthority = null,
        public ?string $accountCode = null,
    ) {}

    /**
     * Create a royalty withholding component.
     */
    public static function royalty(float $rate, Money $amount): self
    {
        return new self(
            code: 'WHT_ROYALTY',
            description: sprintf('Withholding on royalties at %.1f%%', $rate),
            rate: $rate,
            amount: $amount,
        );
    }

    /**
     * Create a service fee withholding component.
     */
    public static function serviceFee(float $rate, Money $amount): self
    {
        return new self(
            code: 'WHT_SERVICE',
            description: sprintf('Withholding on service fees at %.1f%%', $rate),
            rate: $rate,
            amount: $amount,
        );
    }

    /**
     * Create an interest withholding component.
     */
    public static function interest(float $rate, Money $amount): self
    {
        return new self(
            code: 'WHT_INTEREST',
            description: sprintf('Withholding on interest at %.1f%%', $rate),
            rate: $rate,
            amount: $amount,
        );
    }

    /**
     * Create a dividend withholding component.
     */
    public static function dividend(float $rate, Money $amount): self
    {
        return new self(
            code: 'WHT_DIVIDEND',
            description: sprintf('Withholding on dividends at %.1f%%', $rate),
            rate: $rate,
            amount: $amount,
        );
    }

    /**
     * Create a contractor withholding component.
     */
    public static function contractor(float $rate, Money $amount): self
    {
        return new self(
            code: 'WHT_CONTRACTOR',
            description: sprintf('Contractor withholding at %.1f%%', $rate),
            rate: $rate,
            amount: $amount,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'description' => $this->description,
            'rate' => $this->rate,
            'amount' => $this->amount->getAmount(),
            'currency' => $this->amount->getCurrency(),
            'tax_authority' => $this->taxAuthority,
            'account_code' => $this->accountCode,
        ];
    }
}
