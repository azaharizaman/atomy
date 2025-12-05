<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that contain embedded audit information.
 *
 * Provides standard audit metadata fields for tracking creation and modification
 * timestamps and user identifiers. This is for lightweight audit embedding within
 * value objects, NOT for comprehensive audit logging.
 *
 * **When to Use:**
 * - Value objects that need to track who created/modified them
 * - DTOs carrying audit metadata between layers
 * - Aggregate roots embedding basic audit info
 * - When audit trail is part of the value object's identity
 *
 * **When NOT to Use:**
 * - For comprehensive audit logging → Use `Nexus\AuditLogger`
 * - For change history tracking → Use `Nexus\Audit` package
 * - For event-sourced audit trails → Use `Nexus\EventStream`
 *
 * **Expected Behavior:**
 * - `getCreatedBy()` always returns a non-empty user identifier
 * - `getCreatedAt()` always returns a valid timestamp
 * - `getUpdatedBy()/getUpdatedAt()` return null if never updated
 * - User identifiers should be ULIDs or consistent format
 *
 * @example Example implementation:
 * ```php
 * final readonly class ApprovalRecord implements Auditable
 * {
 *     public function __construct(
 *         private string $id,
 *         private string $status,
 *         private string $createdBy,
 *         private \DateTimeImmutable $createdAt,
 *         private ?string $updatedBy = null,
 *         private ?\DateTimeImmutable $updatedAt = null
 *     ) {}
 *
 *     public function getCreatedBy(): string { return $this->createdBy; }
 *     public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
 *     public function getUpdatedBy(): ?string { return $this->updatedBy; }
 *     public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
 * }
 * ```
 */
interface Auditable
{
    /**
     * Get who created this.
     */
    public function getCreatedBy(): string;

    /**
     * Get when this was created.
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get who last updated this.
     */
    public function getUpdatedBy(): ?string;

    /**
     * Get when this was last updated.
     */
    public function getUpdatedAt(): ?\DateTimeImmutable;
}
