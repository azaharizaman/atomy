<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts\Validation;

/**
 * Validation interface for Unit business rules.
 * Handles existence checks and constraint validation.
 */
interface UnitValidationInterface
{
    /**
     * Check if unit code exists within a company.
     */
    public function codeExists(string $companyId, string $code, ?string $excludeId = null): bool;

    /**
     * Check if staff is a member of a unit.
     */
    public function isMember(string $unitId, string $staffId): bool;
}
