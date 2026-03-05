<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\ValueObjects;

/**
 * Immutable context used to normalize quote values consistently.
 */
final readonly class NormalizationContext
{
    public function __construct(
        public string $baseUnit,
        public string $baseCurrency,
        public ?\DateTimeImmutable $fxLockDate = null
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'base_unit' => $this->baseUnit,
            'base_currency' => $this->baseCurrency,
            'fx_lock_date' => $this->fxLockDate?->format('Y-m-d'),
        ];
    }
}

