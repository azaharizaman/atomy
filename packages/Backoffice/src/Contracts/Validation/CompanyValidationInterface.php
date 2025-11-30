<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts\Validation;

/**
 * Validation interface for Company uniqueness checks.
 *
 * Handles validation operations for companies.
 * Follows ISP by focusing solely on validation operations.
 */
interface CompanyValidationInterface
{
    /**
     * Check if a company code exists.
     *
     * @param string $code Company code to check
     * @param string|null $excludeId Company ID to exclude from check (for updates)
     * @return bool True if code exists
     */
    public function codeExists(string $code, ?string $excludeId = null): bool;

    /**
     * Check if a registration number exists.
     *
     * @param string $registrationNumber Registration number to check
     * @param string|null $excludeId Company ID to exclude from check (for updates)
     * @return bool True if registration number exists
     */
    public function registrationNumberExists(string $registrationNumber, ?string $excludeId = null): bool;
}
