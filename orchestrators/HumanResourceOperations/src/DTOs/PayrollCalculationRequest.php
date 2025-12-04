<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\DTOs;

/**
 * Request DTO for payroll calculation
 */
final readonly class PayrollCalculationRequest
{
    public function __construct(
        public string $employeeId,
        public string $periodId,
        public \DateTimeImmutable $periodStart,
        public \DateTimeImmutable $periodEnd,
        public ?array $adjustments = null,
        public ?array $metadata = null
    ) {}
}
