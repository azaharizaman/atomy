<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Contracts\Query;

use Nexus\Backoffice\Contracts\OfficeInterface;

/**
 * Query interface for Office read operations (CQRS - Query).
 * Handles data retrieval without side effects.
 */
interface OfficeQueryInterface
{
    /**
     * Find office by ID.
     */
    public function findById(string $id): ?OfficeInterface;

    /**
     * Find office by code within a company.
     */
    public function findByCode(string $companyId, string $code): ?OfficeInterface;

    /**
     * Get all offices for a company.
     *
     * @return array<OfficeInterface>
     */
    public function getByCompany(string $companyId): array;

    /**
     * Get offices by geographic location.
     *
     * @return array<OfficeInterface>
     */
    public function getByLocation(string $country, ?string $city = null): array;
}
