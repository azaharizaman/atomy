<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts\Query;

use Nexus\Backoffice\Contracts\CompanyInterface;

/**
 * Query interface for Company read operations (CQRS Query Model).
 *
 * Handles all read operations for companies.
 * Follows ISP by focusing solely on query operations.
 * Follows CQRS by separating reads from writes.
 */
interface CompanyQueryInterface
{
    /**
     * Find a company by its unique identifier.
     *
     * @param string $id Company identifier
     * @return CompanyInterface|null Company or null if not found
     */
    public function findById(string $id): ?CompanyInterface;

    /**
     * Find a company by its unique code.
     *
     * @param string $code Company code
     * @return CompanyInterface|null Company or null if not found
     */
    public function findByCode(string $code): ?CompanyInterface;

    /**
     * Find a company by its registration number.
     *
     * @param string $registrationNumber Company registration number
     * @return CompanyInterface|null Company or null if not found
     */
    public function findByRegistrationNumber(string $registrationNumber): ?CompanyInterface;

    /**
     * Get all companies.
     *
     * @return array<CompanyInterface> All companies
     */
    public function getAll(): array;
}
