<?php

declare(strict_types=1);

namespace Nexus\Payroll\Services;

use DateTimeInterface;
use Nexus\Payroll\Contracts\ComponentQueryInterface;
use Nexus\Payroll\Contracts\EmployeeComponentQueryInterface;
use Nexus\Payroll\Contracts\EmployeeDataProviderInterface;
use Nexus\Payroll\Contracts\PayloadBuilderInterface;
use Nexus\Payroll\Contracts\PayloadInterface;
use Nexus\Payroll\Exceptions\PayloadValidationException;

/**
 * Default implementation of PayloadBuilderInterface.
 * 
 * This implementation aggregates data from multiple sources:
 * - Employee data from HRM (via EmployeeDataProviderInterface)
 * - Earnings from employee components
 * - YTD payroll data
 * 
 * It constructs a complete PayloadInterface for statutory calculations.
 */
final readonly class PayloadBuilder implements PayloadBuilderInterface
{
    public function __construct(
        private EmployeeDataProviderInterface $employeeDataProvider,
        private EmployeeComponentQueryInterface $employeeComponentQuery,
        private ComponentQueryInterface $componentQuery,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function buildPayload(
        string $employeeId,
        DateTimeInterface $periodStart,
        DateTimeInterface $periodEnd,
        array $options = []
    ): PayloadInterface {
        // Get employee data
        $employees = $this->employeeDataProvider->getEmployeesByIds(
            $options['tenantId'] ?? throw new PayloadValidationException('tenantId is required'),
            [$employeeId]
        );

        if (empty($employees)) {
            throw new PayloadValidationException("Employee not found: {$employeeId}");
        }

        $employeeData = $employees[0];
        
        // Calculate year for YTD
        $year = (int) $periodEnd->format('Y');
        
        // Get YTD data
        $ytdData = $this->employeeDataProvider->getEmployeeYtdPayroll($employeeId, $year);
        
        // Get earnings breakdown
        $earningsBreakdown = $this->calculateEarningsBreakdown($employeeId, $periodStart, $periodEnd);
        
        // Calculate gross pay and taxable income
        $grossPay = array_sum($earningsBreakdown);
        
        // Taxable income may differ from gross (e.g., certain allowances are non-taxable)
        // For now, assume gross = taxable, but this can be refined
        $taxableIncome = $grossPay;
        
        // Get basic salary (first earning component marked as basic)
        $basicSalary = $earningsBreakdown['basic_salary'] ?? $grossPay;

        // Build company metadata (would typically come from tenant config)
        $companyMetadata = $options['companyMetadata'] ?? [
            'registration_number' => '',
            'tax_office' => '',
            'branch_code' => '',
        ];

        // Build employee metadata
        $employeeMetadata = [
            'employee_number' => $employeeData->employeeNumber,
            'full_name' => $employeeData->getFullName(),
            'tax_id' => $employeeData->taxId,
            'citizenship' => $employeeData->citizenship,
            'employment_type' => $employeeData->employmentType,
            'pay_group_id' => $employeeData->payGroupId,
            'hire_date' => $employeeData->hireDate->format('Y-m-d'),
            'bank_account' => [
                'name' => $employeeData->bankAccountName,
                'number' => $employeeData->bankAccountNumber,
                'bank' => $employeeData->bankName,
            ],
        ];

        // Build metadata
        $metadata = array_merge($options['metadata'] ?? [], [
            'tenant_id' => $options['tenantId'] ?? null,
            'build_timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'attendance_days' => $options['attendance_days'] ?? null,
            'leave_days' => $options['leave_days'] ?? null,
            'overtime_hours' => $options['overtime_hours'] ?? null,
        ]);

        return new PayrollPayload(
            employeeId: $employeeId,
            employeeMetadata: $employeeMetadata,
            companyMetadata: $companyMetadata,
            grossPay: $grossPay,
            taxableIncome: $taxableIncome,
            basicSalary: $basicSalary,
            earningsBreakdown: $earningsBreakdown,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            ytdGrossPay: $ytdData->ytdGrossPay,
            ytdTaxPaid: $ytdData->ytdTaxPaid,
            metadata: $metadata,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildBatchPayloads(
        array $employeeIds,
        DateTimeInterface $periodStart,
        DateTimeInterface $periodEnd,
        array $options = []
    ): array {
        $payloads = [];
        
        foreach ($employeeIds as $employeeId) {
            try {
                $payloads[$employeeId] = $this->buildPayload(
                    $employeeId,
                    $periodStart,
                    $periodEnd,
                    $options
                );
            } catch (\Throwable $e) {
                // Log error but continue with other employees
                // Could add error handling/monitoring here
                $payloads[$employeeId] = new ErrorPayload($employeeId, $e->getMessage());
            }
        }
        
        return $payloads;
    }

    /**
     * Calculate earnings breakdown for an employee.
     */
    private function calculateEarningsBreakdown(
        string $employeeId,
        DateTimeInterface $periodStart,
        DateTimeInterface $periodEnd
    ): array {
        $earnings = [];
        
        $employeeComponents = $this->employeeComponentQuery->getActiveComponentsForEmployee($employeeId);
        
        foreach ($employeeComponents as $empComponent) {
            $component = $this->componentQuery->findById($empComponent->getComponentId());
            
            if (!$component || $component->getType() !== 'earning') {
                continue;
            }
            
            $code = $component->getCode();
            $amount = $this->calculateComponentAmount($empComponent, $component);
            
            // If this is the first earning, mark it as basic salary
            if (!isset($earnings['basic_salary']) && $component->getCode() === 'basic_salary') {
                $earnings['basic_salary'] = $amount;
            } else {
                $earnings[$code] = $amount;
            }
        }
        
        return $earnings;
    }

    /**
     * Calculate component amount based on calculation method.
     */
    private function calculateComponentAmount($empComponent, $component): float
    {
        // If employee component has override amount, use it
        if ($empComponent->getAmount() !== null) {
            return $empComponent->getAmount();
        }
        
        return match ($component->getCalculationMethod()) {
            'fixed_amount' => $component->getFixedAmount() ?? 0.0,
            default => 0.0,
        };
    }
}

/**
 * Concrete payload implementation for payroll processing.
 */
final readonly class PayrollPayload implements PayloadInterface
{
    public function __construct(
        private string $employeeId,
        private array $employeeMetadata,
        private array $companyMetadata,
        private float $grossPay,
        private float $taxableIncome,
        private float $basicSalary,
        private array $earningsBreakdown,
        private DateTimeInterface $periodStart,
        private DateTimeInterface $periodEnd,
        private float $ytdGrossPay,
        private float $ytdTaxPaid,
        private array $metadata,
    ) {}

    public function getEmployeeId(): string
    {
        return $this->employeeId;
    }

    public function getEmployeeMetadata(): array
    {
        return $this->employeeMetadata;
    }

    public function getCompanyMetadata(): array
    {
        return $this->companyMetadata;
    }

    public function getGrossPay(): float
    {
        return $this->grossPay;
    }

    public function getTaxableIncome(): float
    {
        return $this->taxableIncome;
    }

    public function getBasicSalary(): float
    {
        return $this->basicSalary;
    }

    public function getEarningsBreakdown(): array
    {
        return $this->earningsBreakdown;
    }

    public function getPeriodStart(): DateTimeInterface
    {
        return $this->periodStart;
    }

    public function getPeriodEnd(): DateTimeInterface
    {
        return $this->periodEnd;
    }

    public function getYtdGrossPay(): float
    {
        return $this->ytdGrossPay;
    }

    public function getYtdTaxPaid(): float
    {
        return $this->ytdTaxPaid;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}

/**
 * Error payload for failed payroll calculations.
 */
final readonly class ErrorPayload implements PayloadInterface
{
    private \DateTimeImmutable $errorTimestamp;
    
    public function __construct(
        private string $employeeId,
        private string $errorMessage,
    ) {
        $this->errorTimestamp = new \DateTimeImmutable();
    }

    public function getEmployeeId(): string
    {
        return $this->employeeId;
    }

    public function getEmployeeMetadata(): array
    {
        return [];
    }

    public function getCompanyMetadata(): array
    {
        return [];
    }

    public function getGrossPay(): float
    {
        return 0.0;
    }

    public function getTaxableIncome(): float
    {
        return 0.0;
    }

    public function getBasicSalary(): float
    {
        return 0.0;
    }

    public function getEarningsBreakdown(): array
    {
        return [];
    }

    public function getPeriodStart(): DateTimeInterface
    {
        return $this->errorTimestamp;
    }

    public function getPeriodEnd(): DateTimeInterface
    {
        return $this->errorTimestamp;
    }

    public function getYtdGrossPay(): float
    {
        return 0.0;
    }

    public function getYtdTaxPaid(): float
    {
        return 0.0;
    }

    public function getMetadata(): array
    {
        return [
            'error' => $this->errorMessage,
            'error_timestamp' => $this->errorTimestamp->format(\DateTimeInterface::ATOM),
        ];
    }
}
