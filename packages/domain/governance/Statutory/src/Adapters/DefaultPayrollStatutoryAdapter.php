<?php

declare(strict_types=1);

namespace Nexus\Statutory\Adapters;

use Nexus\Statutory\Contracts\PayrollStatutoryInterface;
use Nexus\Statutory\Exceptions\CalculationException;
use Nexus\Statutory\Exceptions\InvalidDeductionTypeException;
use Psr\Log\LoggerInterface;

/**
 * Default payroll statutory calculator with zero deductions.
 * 
 * This is a safe default implementation that can be used when no country-specific
 * adapter is available or for testing purposes.
 */
final class DefaultPayrollStatutoryAdapter implements PayrollStatutoryInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function calculateDeductions(
        string $tenantId,
        string $employeeId,
        float $grossSalary,
        \DateTimeImmutable $payDate,
        array $employeeData = []
    ): array {
        $this->logger->info("Using default payroll statutory adapter (zero deductions)", [
            'tenant_id' => $tenantId,
            'employee_id' => $employeeId,
            'gross_salary' => $grossSalary,
        ]);

        // Default implementation returns zero deductions
        return [];
    }

    public function getCountryCode(): string
    {
        return 'DEFAULT';
    }

    public function getSupportedDeductionTypes(): array
    {
        // Default adapter supports no deduction types
        return [];
    }

    public function getEffectiveRate(
        string $deductionType,
        float $grossSalary,
        \DateTimeImmutable $effectiveDate
    ): float {
        throw new InvalidDeductionTypeException($deductionType, $this->getCountryCode());
    }
}
