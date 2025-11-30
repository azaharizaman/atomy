<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

/**
 * Persistence contract for payslip write operations.
 *
 * Implements CQRS pattern - write operations only.
 */
interface PayslipPersistInterface
{
    /**
     * Create a new payslip.
     *
     * @param array<string, mixed> $data Payslip data
     * @return PayslipInterface Created payslip
     */
    public function create(array $data): PayslipInterface;

    /**
     * Update an existing payslip.
     *
     * @param string $id Payslip ULID
     * @param array<string, mixed> $data Updated data
     * @return PayslipInterface Updated payslip
     */
    public function update(string $id, array $data): PayslipInterface;

    /**
     * Delete a payslip.
     *
     * @param string $id Payslip ULID
     * @return bool True if deleted successfully
     */
    public function delete(string $id): bool;
}
