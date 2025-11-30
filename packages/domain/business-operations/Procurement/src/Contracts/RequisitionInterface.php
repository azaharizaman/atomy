<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Purchase requisition entity interface.
 */
interface RequisitionInterface
{
    /**
     * Get requisition ID.
     *
     * @return string ULID
     */
    public function getId(): string;

    /**
     * Get requisition number.
     *
     * @return string e.g., "REQ-2024-001"
     */
    public function getRequisitionNumber(): string;

    /**
     * Get requester user ID.
     *
     * @return string User ULID
     */
    public function getRequesterId(): string;

    /**
     * Get requisition status.
     *
     * @return string draft|pending_approval|approved|rejected|converted
     */
    public function getStatus(): string;

    /**
     * Get total estimated amount.
     *
     * @return float
     */
    public function getTotalEstimate(): float;

    /**
     * Get requisition lines.
     *
     * @return array<RequisitionLineInterface>
     */
    public function getLines(): array;

    /**
     * Get approved by user ID.
     *
     * @return string|null User ULID
     */
    public function getApprovedBy(): ?string;

    /**
     * Get approval timestamp.
     *
     * @return \DateTimeImmutable|null
     */
    public function getApprovedAt(): ?\DateTimeImmutable;

    /**
     * Get created timestamp.
     *
     * @return \DateTimeImmutable
     */
    public function getCreatedAt(): \DateTimeImmutable;
}
