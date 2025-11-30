<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Contracts;

use DateTimeImmutable;
use Nexus\FeatureFlags\Enums\AuditAction;

/**
 * Represents a single audit record for a feature flag change.
 *
 * This interface defines the structure of an audit entry returned by
 * FlagAuditQueryInterface methods.
 */
interface FlagAuditRecordInterface
{
    /**
     * Get the unique identifier of this audit record.
     *
     * @return string ULID or UUID of the audit record
     */
    public function getId(): string;

    /**
     * Get the name of the flag that was changed.
     *
     * @return string The flag name
     */
    public function getFlagName(): string;

    /**
     * Get the action that was performed.
     *
     * @return AuditAction The type of change
     */
    public function getAction(): AuditAction;

    /**
     * Get the ID of the user who made the change.
     *
     * @return string|null User ID, or null for system-initiated changes
     */
    public function getUserId(): ?string;

    /**
     * Get the tenant ID scope of the change.
     *
     * @return string|null Tenant ID, or null for global flags
     */
    public function getTenantId(): ?string;

    /**
     * Get the state before the change.
     *
     * @return array<string, mixed>|null Previous state, or null for create operations
     */
    public function getBefore(): ?array;

    /**
     * Get the state after the change.
     *
     * @return array<string, mixed>|null New state, or null for delete operations
     */
    public function getAfter(): ?array;

    /**
     * Get the reason provided for the change.
     *
     * @return string|null Reason if provided, null otherwise
     */
    public function getReason(): ?string;

    /**
     * Get additional metadata about the change.
     *
     * May include:
     * - ip_address: IP address of the requester
     * - user_agent: Browser/client user agent
     * - batch_id: ID if part of a batch operation
     * - severity: For critical operations
     *
     * @return array<string, mixed> Metadata array
     */
    public function getMetadata(): array;

    /**
     * Get when the change occurred.
     *
     * @return DateTimeImmutable Timestamp of the change
     */
    public function getOccurredAt(): DateTimeImmutable;

    /**
     * Check if this is a critical audit event.
     *
     * @return bool True if the action is marked as critical
     */
    public function isCritical(): bool;

    /**
     * Get the sequence number for this event (for ordering).
     *
     * @return int|null Sequence number if using event sourcing, null otherwise
     */
    public function getSequence(): ?int;
}
