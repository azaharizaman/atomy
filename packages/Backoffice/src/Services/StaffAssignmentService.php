<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Services;

use Nexus\Backoffice\Contracts\StaffInterface;
use Nexus\Backoffice\Contracts\Query\StaffQueryInterface;
use Nexus\Backoffice\Enums\StaffStatus;

/**
 * Domain service for Staff assignment and hierarchy operations.
 * Extracted from StaffRepositoryInterface to follow ISP principle.
 */
final readonly class StaffAssignmentService
{
    public function __construct(
        private StaffQueryInterface $staffQuery
    ) {}

    /**
     * Get all active staff for a company.
     *
     * @return array<StaffInterface>
     */
    public function getActiveByCompany(string $companyId): array
    {
        $staff = $this->staffQuery->getByCompany($companyId);

        return array_filter(
            $staff,
            fn(StaffInterface $s): bool => $s->getStatus() === StaffStatus::Active
        );
    }

    /**
     * Get direct reports for a supervisor.
     *
     * @return array<StaffInterface>
     */
    public function getDirectReports(string $supervisorId): array
    {
        $allStaff = $this->staffQuery->getByCompany($this->getCompanyId($supervisorId));

        return array_filter(
            $allStaff,
            fn(StaffInterface $s): bool => $s->getSupervisorId() === $supervisorId
        );
    }

    /**
     * Get all reports (direct and indirect) for a supervisor.
     *
     * @return array<StaffInterface>
     */
    public function getAllReports(string $supervisorId): array
    {
        $directReports = $this->getDirectReports($supervisorId);
        $allReports = $directReports;

        foreach ($directReports as $report) {
            $indirectReports = $this->getAllReports($report->getId());
            $allReports = array_merge($allReports, $indirectReports);
        }

        return $allReports;
    }

    /**
     * Get supervisor chain from staff to top-level supervisor.
     *
     * @return array<StaffInterface>
     */
    public function getSupervisorChain(string $staffId): array
    {
        $chain = [];
        $visited = [$staffId => true]; // Track visited IDs
        $currentStaff = $this->staffQuery->findById($staffId);

        while ($currentStaff !== null && $currentStaff->getSupervisorId() !== null) {
            $supervisorId = $currentStaff->getSupervisorId();

            // Detect circular reference immediately
            if (isset($visited[$supervisorId])) {
                break;
            }

            $supervisor = $this->staffQuery->findById($supervisorId);
            if ($supervisor === null) {
                break;
            }

            $chain[] = $supervisor;
            $visited[$supervisorId] = true;
            $currentStaff = $supervisor;
        }

        return $chain;
    }

    /**
     * Get depth of supervisor chain for a staff member.
     */
    public function getSupervisorChainDepth(string $staffId): int
    {
        return count($this->getSupervisorChain($staffId));
    }

    /**
     * Helper to get company ID from staff ID.
     */
    private function getCompanyId(string $staffId): string
    {
        $staff = $this->staffQuery->findById($staffId);
        if ($staff === null) {
            throw new \InvalidArgumentException("Staff with ID {$staffId} not found");
        }
        return $staff->getCompanyId();
    }
}
