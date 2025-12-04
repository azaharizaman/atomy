<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\DTOs;

use Nexus\Common\ValueObjects\Money;

/**
 * Result DTO for payroll calculation
 */
final readonly class PayrollCalculationResult
{
    public function __construct(
        public bool $success,
        public string $payslipId,
        public Money $grossPay,
        public Money $netPay,
        public array $earnings,
        public array $deductions,
        public array $validationMessages = [],
        public ?string $message = null
    ) {}

    public function hasWarnings(): bool
    {
        return !empty($this->validationMessages);
    }
}
