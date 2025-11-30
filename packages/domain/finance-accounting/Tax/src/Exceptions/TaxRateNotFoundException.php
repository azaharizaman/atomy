<?php

declare(strict_types=1);

namespace Nexus\Tax\Exceptions;

/**
 * Tax Rate Not Found Exception
 * 
 * Thrown when a requested tax rate cannot be found in the repository.
 * Includes temporal context (effective date).
 */
final class TaxRateNotFoundException extends \Exception
{
    public function __construct(
        private readonly string $taxCode,
        private readonly \DateTimeInterface $effectiveDate,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            "Tax rate '%s' not found for effective date %s",
            $taxCode,
            $effectiveDate->format('Y-m-d')
        );

        parent::__construct($message, 0, $previous);
    }

    public function getTaxCode(): string
    {
        return $this->taxCode;
    }

    public function getEffectiveDate(): \DateTimeInterface
    {
        return $this->effectiveDate;
    }

    public function getContext(): array
    {
        return [
            'tax_code' => $this->taxCode,
            'effective_date' => $this->effectiveDate->format('Y-m-d H:i:s'),
        ];
    }
}
