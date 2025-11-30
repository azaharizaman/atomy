<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Support;

use Nexus\Tax\Contracts\TaxRateRepositoryInterface;
use Nexus\Tax\Exceptions\TaxRateNotFoundException;
use Nexus\Tax\ValueObjects\TaxRate;

/**
 * In-Memory Tax Rate Repository
 * 
 * Test double for integration testing without database dependency.
 */
final class InMemoryTaxRateRepository implements TaxRateRepositoryInterface
{
    /** @var array<string, TaxRate[]> */
    private array $rates = [];

    public function addRate(TaxRate $rate): void
    {
        $this->rates[$rate->taxCode][] = $rate;
    }

    public function findByCode(string $taxCode, \DateTimeInterface $effectiveDate): TaxRate
    {
        if (!isset($this->rates[$taxCode])) {
            throw new TaxRateNotFoundException($taxCode, $effectiveDate);
        }

        foreach ($this->rates[$taxCode] as $rate) {
            if ($rate->isEffectiveOn($effectiveDate)) {
                return $rate;
            }
        }

        throw new TaxRateNotFoundException($taxCode, $effectiveDate);
    }

    public function findByJurisdiction(
        string $jurisdictionCode,
        string $taxType,
        \DateTimeInterface $effectiveDate
    ): array {
        $result = [];

        foreach ($this->rates as $rateList) {
            foreach ($rateList as $rate) {
                if ($rate->jurisdictionCode === $jurisdictionCode
                    && $rate->type === $taxType
                    && $rate->isEffectiveOn($effectiveDate)
                ) {
                    $result[] = $rate;
                }
            }
        }

        return $result;
    }

    public function exists(string $taxCode, \DateTimeInterface $effectiveDate): bool
    {
        try {
            $this->findByCode($taxCode, $effectiveDate);
            return true;
        } catch (TaxRateNotFoundException) {
            return false;
        }
    }
}
