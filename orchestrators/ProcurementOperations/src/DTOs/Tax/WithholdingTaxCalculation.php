<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Tax;

use Nexus\Common\ValueObjects\Money;

/**
 * Result of withholding tax calculation.
 */
final readonly class WithholdingTaxCalculation
{
    /**
     * @param array<WithholdingTaxComponent> $components Breakdown of withholding components
     * @param array<string, mixed> $metadata Additional calculation metadata
     */
    public function __construct(
        public string $vendorId,
        public Money $grossAmount,
        public Money $withholdingAmount,
        public Money $netPayable,
        public float $effectiveRate,
        public array $components,
        public string $jurisdiction,
        public bool $certificateOnFile,
        public ?string $treatyApplied,
        public \DateTimeImmutable $calculatedAt,
        public array $metadata = [],
    ) {}

    /**
     * Create calculation with no withholding required.
     */
    public static function noWithholding(
        string $vendorId,
        Money $grossAmount,
        string $jurisdiction,
        string $reason = 'Not applicable',
    ): self {
        return new self(
            vendorId: $vendorId,
            grossAmount: $grossAmount,
            withholdingAmount: Money::of(0, $grossAmount->getCurrency()),
            netPayable: $grossAmount,
            effectiveRate: 0.0,
            components: [],
            jurisdiction: $jurisdiction,
            certificateOnFile: true,
            treatyApplied: null,
            calculatedAt: new \DateTimeImmutable(),
            metadata: ['reason' => $reason],
        );
    }

    /**
     * Create calculation with standard withholding.
     *
     * @param array<WithholdingTaxComponent> $components
     */
    public static function withWithholding(
        string $vendorId,
        Money $grossAmount,
        Money $withholdingAmount,
        array $components,
        string $jurisdiction,
        bool $certificateOnFile = false,
        ?string $treatyApplied = null,
    ): self {
        $netPayable = Money::of(
            $grossAmount->getAmount() - $withholdingAmount->getAmount(),
            $grossAmount->getCurrency(),
        );

        $effectiveRate = $grossAmount->isZero()
            ? 0.0
            : ($withholdingAmount->getAmount() / $grossAmount->getAmount()) * 100;

        return new self(
            vendorId: $vendorId,
            grossAmount: $grossAmount,
            withholdingAmount: $withholdingAmount,
            netPayable: $netPayable,
            effectiveRate: $effectiveRate,
            components: $components,
            jurisdiction: $jurisdiction,
            certificateOnFile: $certificateOnFile,
            treatyApplied: $treatyApplied,
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create calculation with treaty rate applied.
     */
    public static function withTreatyRate(
        string $vendorId,
        Money $grossAmount,
        float $treatyRate,
        string $treatyName,
        string $jurisdiction,
    ): self {
        $withholdingAmount = $grossAmount->multiply($treatyRate / 100);
        $netPayable = Money::of(
            $grossAmount->getAmount() - $withholdingAmount->getAmount(),
            $grossAmount->getCurrency(),
        );

        return new self(
            vendorId: $vendorId,
            grossAmount: $grossAmount,
            withholdingAmount: $withholdingAmount,
            netPayable: $netPayable,
            effectiveRate: $treatyRate,
            components: [
                new WithholdingTaxComponent(
                    code: 'TREATY_WHT',
                    description: "Treaty withholding under {$treatyName}",
                    rate: $treatyRate,
                    amount: $withholdingAmount,
                ),
            ],
            jurisdiction: $jurisdiction,
            certificateOnFile: true,
            treatyApplied: $treatyName,
            calculatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Check if withholding applies.
     */
    public function hasWithholding(): bool
    {
        return !$this->withholdingAmount->isZero();
    }

    /**
     * Check if treaty benefit was applied.
     */
    public function hasTreatyBenefit(): bool
    {
        return $this->treatyApplied !== null;
    }

    /**
     * Get component descriptions for reporting.
     *
     * @return array<string>
     */
    public function getComponentDescriptions(): array
    {
        return array_map(
            fn(WithholdingTaxComponent $component) => $component->description,
            $this->components,
        );
    }
}
