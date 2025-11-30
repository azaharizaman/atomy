<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Contracts;

/**
 * Contract for Engineering Change Order entity.
 *
 * Manages versions and effectivity transitions for BOMs and Routings.
 */
interface ChangeOrderInterface
{
    /**
     * Get the unique identifier.
     */
    public function getId(): string;

    /**
     * Get the tenant identifier.
     */
    public function getTenantId(): string;

    /**
     * Get the change order number.
     */
    public function getOrderNumber(): string;

    /**
     * Get the change order title/subject.
     */
    public function getTitle(): string;

    /**
     * Get the detailed description of changes.
     */
    public function getDescription(): string;

    /**
     * Get the change order status (draft, approved, released, cancelled).
     */
    public function getStatus(): string;

    /**
     * Get the type of change (bom, routing, both).
     */
    public function getChangeType(): string;

    /**
     * Get the affected BOM ID.
     */
    public function getBomId(): ?string;

    /**
     * Get the affected routing ID.
     */
    public function getRoutingId(): ?string;

    /**
     * Get the old version number.
     */
    public function getOldVersion(): int;

    /**
     * Get the new version number.
     */
    public function getNewVersion(): int;

    /**
     * Get the date the new version becomes effective.
     */
    public function getEffectiveFrom(): \DateTimeImmutable;

    /**
     * Get the date the old version expires.
     * Usually same as effectiveFrom or slightly before.
     */
    public function getEffectiveTo(): ?\DateTimeImmutable;

    /**
     * Get the reason for change.
     */
    public function getReason(): string;

    /**
     * Get the requestor user ID.
     */
    public function getRequestedBy(): string;

    /**
     * Get the approver user ID.
     */
    public function getApprovedBy(): ?string;

    /**
     * Get the approval date.
     */
    public function getApprovedAt(): ?\DateTimeImmutable;

    /**
     * Get creation timestamp.
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get last update timestamp.
     */
    public function getUpdatedAt(): \DateTimeImmutable;
}
