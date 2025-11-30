<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts\Validation;

/**
 * Validation interface for Office business rules.
 * Handles existence checks and constraint validation.
 */
interface OfficeValidationInterface
{
    /**
     * Check if office code exists within a company.
     */
    public function codeExists(string $companyId, string $code, ?string $excludeId = null): bool;

    /**
     * Check if office has active staff assigned.
     */
    public function hasActiveStaff(string $officeId): bool;

    /**
     * Check if company has a head office.
     */
    public function hasHeadOffice(string $companyId, ?string $excludeId = null): bool;
}
