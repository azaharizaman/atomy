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
}
