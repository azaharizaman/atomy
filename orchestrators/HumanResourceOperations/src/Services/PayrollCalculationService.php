<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\HumanResourceOperations\DTOs\PayrollContext;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for payroll calculations
 * 
 * @skeleton Requires implementation of calculation logic
 */
final readonly class PayrollCalculationService
{
    public function __construct(
        // TODO: Inject statutory calculators from Nexus\PayrollMysStatutory
        // private StatutoryCalculatorInterface $statutoryCalculator,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * Calculate gross pay from all earnings
     */
    public function calculateGrossPay(PayrollContext $context): Money
    {
        $this->logger->info('Calculating gross pay', [
            'employee_id' => $context->employeeId,
            'period_id' => $context->periodId
        ]);

        $total = $context->baseSalary;

        // Add all earnings
        foreach ($context->earnings as $component => $amount) {
            if ($amount instanceof Money) {
                $total = $total->add($amount);
            } elseif (is_numeric($amount)) {
                $total = $total->add(Money::of((float) $amount, $total->currency));
            }
        }

        return $total;
    }

    /**
     * Calculate net pay (gross - deductions)
     */
    public function calculateNetPay(PayrollContext $context): Money
    {
        $grossPay = $this->calculateGrossPay($context);
        
        $this->logger->info('Calculating net pay', [
            'employee_id' => $context->employeeId,
            'gross_pay' => $grossPay->toArray()
        ]);

        $totalDeductions = Money::zero($grossPay->currency);

        // Sum all deductions
        foreach ($context->deductions as $component => $amount) {
            if ($amount instanceof Money) {
                $totalDeductions = $totalDeductions->add($amount);
            } elseif (is_numeric($amount)) {
                $totalDeductions = $totalDeductions->add(
                    Money::of((float) $amount, $grossPay->currency)
                );
            }
        }

        return $grossPay->subtract($totalDeductions);
    }

    /**
     * Calculate overtime pay
     * 
     * @skeleton
     */
    public function calculateOvertimePay(
        Money $hourlyRate,
        float $overtimeHours,
        float $overtimeMultiplier = 1.5
    ): Money {
        // TODO: Implement overtime calculation based on Malaysian Employment Act
        // - Normal OT: 1.5x for first 2 hours
        // - Extended OT: 2.0x after 2 hours
        // - Rest day: 2.0x
        // - Public holiday: 3.0x
        
        $amount = $hourlyRate->amountInCents * $overtimeHours * $overtimeMultiplier;
        
        return Money::of((int) $amount, $hourlyRate->currency);
    }

    /**
     * Calculate statutory deductions (EPF, SOCSO, PCB)
     * 
     * @skeleton
     */
    public function calculateStatutoryDeductions(Money $grossPay): array
    {
        // TODO: Implement via Nexus\PayrollMysStatutory
        // - EPF: Employee contribution (11%) + Employer contribution (12-13%)
        // - SOCSO: Based on wage brackets
        // - PCB: Based on tax brackets
        
        return [
            'epf_employee' => Money::zero($grossPay->currency),
            'epf_employer' => Money::zero($grossPay->currency),
            'socso_employee' => Money::zero($grossPay->currency),
            'socso_employer' => Money::zero($grossPay->currency),
            'income_tax' => Money::zero($grossPay->currency)
        ];
    }
}
