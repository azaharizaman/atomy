<?php

declare(strict_types=1);

namespace Nexus\Hrm\Contracts;

/**
 * Repository contract for leave type persistence operations.
 */
interface LeaveTypeRepositoryInterface
{
    /**
     * Find leave type by ID.
     *
     * @param string $id Leave type ULID
     * @return LeaveTypeInterface|null
     */
    public function findById(string $id): ?LeaveTypeInterface;
    
    /**
     * Find leave type by code.
     *
     * @param string $tenantId Tenant ULID
     * @param string $code Leave type code
     * @return LeaveTypeInterface|null
     */
    public function findByCode(string $tenantId, string $code): ?LeaveTypeInterface;
    
    /**
     * Get all active leave types for tenant.
     *
     * @param string $tenantId Tenant ULID
     * @return array<LeaveTypeInterface>
     */
    public function getActiveLeaveTypes(string $tenantId): array;
    
    /**
     * Get all leave types for tenant.
     *
     * @param string $tenantId Tenant ULID
     * @param bool $activeOnly
     * @return array<LeaveTypeInterface>
     */
    public function getAll(string $tenantId, bool $activeOnly = false): array;
    
    /**
     * Create a leave type.
     *
     * @param array<string, mixed> $data
     * @return LeaveTypeInterface
     * @throws \Nexus\Hrm\Exceptions\LeaveTypeValidationException
     * @throws \Nexus\Hrm\Exceptions\LeaveTypeDuplicateException
     */
    public function create(array $data): LeaveTypeInterface;
    
    /**
     * Update a leave type.
     *
     * @param string $id Leave type ULID
     * @param array<string, mixed> $data
     * @return LeaveTypeInterface
     * @throws \Nexus\Hrm\Exceptions\LeaveTypeNotFoundException
     * @throws \Nexus\Hrm\Exceptions\LeaveTypeValidationException
     */
    public function update(string $id, array $data): LeaveTypeInterface;
    
    /**
     * Delete a leave type.
     *
     * @param string $id Leave type ULID
     * @return bool
     * @throws \Nexus\Hrm\Exceptions\LeaveTypeNotFoundException
     */
    public function delete(string $id): bool;
}
