<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

use DateTimeInterface;

/**
 * Repository contract for leave request persistence operations.
 */
interface LeaveRepositoryInterface
{
    /**
     * Find leave request by ID.
     *
     * @param string $id Leave ULID
     * @return LeaveInterface|null
     */
    public function findById(string $id): ?LeaveInterface;
    
    /**
     * Get leave requests for employee with filters.
     *
     * @param string $employeeId Employee ULID
     * @param array<string, mixed> $filters
     * @return array<LeaveInterface>
     */
    public function getEmployeeLeaves(string $employeeId, array $filters = []): array;
    
    /**
     * Get pending leave requests for approver.
     *
     * @param string $approverId Approver's employee ULID
     * @return array<LeaveInterface>
     */
    public function getPendingApprovalsForApprover(string $approverId): array;
    
    /**
     * Get leave requests in date range.
     *
     * @param string $tenantId Tenant ULID
     * @param DateTimeInterface $startDate
     * @param DateTimeInterface $endDate
     * @param array<string, mixed> $filters
     * @return array<LeaveInterface>
     */
    public function getLeavesInDateRange(
        string $tenantId,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        array $filters = []
    ): array;
    
    /**
     * Check for overlapping leave requests.
     *
     * @param string $employeeId Employee ULID
     * @param DateTimeInterface $startDate
     * @param DateTimeInterface $endDate
     * @param string|null $excludeId Leave ID to exclude from check
     * @return bool
     */
    public function hasOverlappingLeave(
        string $employeeId,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        ?string $excludeId = null
    ): bool;
    
    /**
     * Create a leave request.
     *
     * @param array<string, mixed> $data
     * @return LeaveInterface
     * @throws \Nexus\Hrm\Exceptions\LeaveValidationException
     * @throws \Nexus\Hrm\Exceptions\LeaveOverlapException
     */
    public function create(array $data): LeaveInterface;
    
    /**
     * Update a leave request.
     *
     * @param string $id Leave ULID
     * @param array<string, mixed> $data
     * @return LeaveInterface
     * @throws \Nexus\Hrm\Exceptions\LeaveNotFoundException
     * @throws \Nexus\Hrm\Exceptions\LeaveValidationException
     */
    public function update(string $id, array $data): LeaveInterface;
    
    /**
     * Delete a leave request.
     *
     * @param string $id Leave ULID
     * @return bool
     * @throws \Nexus\Hrm\Exceptions\LeaveNotFoundException
     */
    public function delete(string $id): bool;
}
