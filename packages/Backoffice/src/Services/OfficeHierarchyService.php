<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Services;

use Nexus\Backoffice\Contracts\OfficeInterface;
use Nexus\Backoffice\Contracts\Query\OfficeQueryInterface;
use Nexus\Backoffice\Enums\OfficeStatus;

/**
 * Domain service for Office business logic operations.
 * Extracted from OfficeRepositoryInterface to follow ISP principle.
 */
final readonly class OfficeHierarchyService
{
    public function __construct(
        private OfficeQueryInterface $officeQuery
    ) {}

    /**
     * Get all active offices for a company.
     *
     * @return array<OfficeInterface>
     */
    public function getActiveByCompany(string $companyId): array
    {
        $offices = $this->officeQuery->getByCompany($companyId);

        return array_filter(
            $offices,
            fn(OfficeInterface $office): bool => $office->getStatus() === OfficeStatus::Active
        );
    }

    /**
     * Get the head office for a company.
     */
    public function getHeadOffice(string $companyId): ?OfficeInterface
    {
        $offices = $this->officeQuery->getByCompany($companyId);

        foreach ($offices as $office) {
            if ($office->isHeadOffice()) {
                return $office;
            }
        }

        return null;
    }
}
