<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Services;

use Nexus\Backoffice\Contracts\UnitInterface;
use Nexus\Backoffice\Contracts\Query\UnitQueryInterface;
use Nexus\Backoffice\Enums\UnitStatus;

/**
 * Domain service for Unit business logic operations.
 * Extracted from UnitRepositoryInterface to follow ISP principle.
 */
final readonly class UnitManagementService
{
    public function __construct(
        private UnitQueryInterface $unitQuery
    ) {}

    /**
     * Get all active units for a company.
     *
     * @return array<UnitInterface>
     */
    public function getActiveByCompany(string $companyId): array
    {
        $units = $this->unitQuery->getByCompany($companyId);

        return array_filter(
            $units,
            fn(UnitInterface $unit): bool => $unit->getStatus() === UnitStatus::Active
        );
    }
}
