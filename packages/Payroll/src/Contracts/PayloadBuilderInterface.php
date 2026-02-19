<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

use DateTimeInterface;

/**
 * Contract for building payroll payloads.
 * 
 * This interface defines how payroll payloads are constructed for
 * statutory calculations. Different implementations may aggregate
 * data from various sources (HRM, Attendance, Leave, etc.).
 */
interface PayloadBuilderInterface
{
    /**
     * Build a complete payroll payload for an employee.
     *
     * @param string $employeeId Employee ULID
     * @param DateTimeInterface $periodStart Payroll period start date
     * @param DateTimeInterface $periodEnd Payroll period end date
     * @param array<string, mixed> $options Additional options for payload building
     * @return PayloadInterface Complete payload for statutory calculations
     */
    public function buildPayload(
        string $employeeId,
        DateTimeInterface $periodStart,
        DateTimeInterface $periodEnd,
        array $options = []
    ): PayloadInterface;

    /**
     * Build payloads for multiple employees in batch.
     *
     * @param array<string> $employeeIds List of employee ULIDs
     * @param DateTimeInterface $periodStart Payroll period start date
     * @param DateTimeInterface $periodEnd Payroll period end date
     * @param array<string, mixed> $options Additional options for payload building
     * @return array<string, PayloadInterface> Map of employee IDs to their payloads
     */
    public function buildBatchPayloads(
        array $employeeIds,
        DateTimeInterface $periodStart,
        DateTimeInterface $periodEnd,
        array $options = []
    ): array;
}
