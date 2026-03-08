<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Requisition repository interface.
 */
interface RequisitionRepositoryInterface
{
    /**
     * Find requisition by ID.
     *
     * @param string $id Requisition ULID
     * @return RequisitionInterface|null
     */
    public function findById(string $id): ?RequisitionInterface;

    /**
     * Find requisition by number.
     *
     * @param string $tenantId Tenant ULID
     * @param string $requisitionNumber Requisition number
     * @return RequisitionInterface|null
     */
    public function findByNumber(string $tenantId, string $requisitionNumber): ?RequisitionInterface;

    /**
     * Save requisition.
     *
     * @param RequisitionInterface $requisition
     * @return void
     */
    public function save(RequisitionInterface $requisition): void;

    /**
     * Generate next requisition number.
     *
     * @param string $tenantId Tenant ULID
     * @return string Next requisition number
     */
    public function generateNextNumber(string $tenantId): string;

    /**
     * Create requisition.
     *
     * @param string $tenantId
     * @param string $requesterId
     * @param array $data
     * @return RequisitionInterface
     */
    public function create(string $tenantId, string $requesterId, array $data): RequisitionInterface;

    /**
     * Update requisition status.
     *
     * @param string $requisitionId
     * @param string $status
     * @return RequisitionInterface
     */
    public function updateStatus(string $requisitionId, string $status): RequisitionInterface;

    /**
     * Approve requisition.
     *
     * @param string $requisitionId
     * @param string $approverId
     * @return RequisitionInterface
     */
    public function approve(string $requisitionId, string $approverId): RequisitionInterface;

    /**
     * Reject requisition.
     *
     * @param string $requisitionId
     * @param string $rejectorId
     * @param string $reason
     * @return RequisitionInterface
     */
    public function reject(string $requisitionId, string $rejectorId, string $reason): RequisitionInterface;

    /**
     * Mark requisition as converted to PO.
     *
     * @param string $requisitionId
     * @param string $poId
     * @return RequisitionInterface
     */
    public function markAsConverted(string $requisitionId, string $poId): RequisitionInterface;

    /**
     * Find requisitions by tenant.
     *
     * @param string $tenantId
     * @param array<string, mixed> $filters
     * @return array<RequisitionInterface>
     */
    public function findByTenantId(string $tenantId, array $filters): array;

    /**
     * Find requisitions by status.
     *
     * @param string $tenantId
     * @param string $status
     * @return array<RequisitionInterface>
     */
    public function findByStatus(string $tenantId, string $status): array;
}
