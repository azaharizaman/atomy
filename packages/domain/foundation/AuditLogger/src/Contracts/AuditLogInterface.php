<?php

declare(strict_types=1);

namespace Nexus\AuditLogger\Contracts;

/**
 * Interface AuditLogInterface
 *
 * Defines the data structure contract for an audit log entry.
 * Satisfies: ARC-AUD-0002, BUS-AUD-0145, BUS-AUD-0146
 *
 * @package Nexus\AuditLogger\Contracts
 */
interface AuditLogInterface
{
    /**
     * Get the unique identifier for the audit log
     *
     * @return int|string|null
     */
    public function getId();

    /**
     * Get the log name/category (e.g., 'user_update', 'order_created')
     * Required field per BUS-AUD-0145
     *
     * @return string
     */
    public function getLogName(): string;

    /**
     * Get the human-readable description of the action
     * Required field per BUS-AUD-0145
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get the type of the subject entity (e.g., 'User', 'Order')
     *
     * @return string|null
     */
    public function getSubjectType(): ?string;

    /**
     * Get the ID of the subject entity
     *
     * @return int|string|null
     */
    public function getSubjectId();

    /**
     * Get the type of the causer (who performed the action)
     * NULL for system activities per BUS-AUD-0148
     *
     * @return string|null
     */
    public function getCauserType(): ?string;

    /**
     * Get the ID of the causer
     * NULL for system activities per BUS-AUD-0148
     *
     * @return int|string|null
     */
    public function getCauserId();

    /**
     * Get additional properties (before/after state, metadata)
     * Satisfies FUN-AUD-0186 (before/after state)
     *
     * @return array
     */
    public function getProperties(): array;

    /**
     * Get the event type (e.g., 'created', 'updated', 'deleted')
     * Satisfies FUN-AUD-0185 (CRUD operations)
     *
     * @return string|null
     */
    public function getEvent(): ?string;

    /**
     * Get the audit level (1=Low, 2=Medium, 3=High, 4=Critical)
     * Required per BUS-AUD-0146
     *
     * @return int
     */
    public function getLevel(): int;

    /**
     * Get the batch UUID for grouping related operations
     * Satisfies BUS-AUD-0150, FUN-AUD-0193
     *
     * @return string|null
     */
    public function getBatchUuid(): ?string;

    /**
     * Get the IP address of the request
     * Satisfies FUN-AUD-0187 (user context)
     *
     * @return string|null
     */
    public function getIpAddress(): ?string;

    /**
     * Get the user agent string
     * Satisfies FUN-AUD-0187 (user context)
     *
     * @return string|null
     */
    public function getUserAgent(): ?string;

    /**
     * Get the tenant/organization ID for multi-tenancy
     * Satisfies FUN-AUD-0188 (tenant isolation)
     *
     * @return int|string|null
     */
    public function getTenantId();

    /**
     * Get the retention period in days
     * Satisfies BUS-AUD-0147, FUN-AUD-0194
     *
     * @return int Default 90 days
     */
    public function getRetentionDays(): int;

    /**
     * Get the timestamp when the log was created
     * Required field per BUS-AUD-0145
     *
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface;

    /**
     * Get the expiration date based on retention policy
     * Satisfies BUS-AUD-0151 (automatic purging)
     *
     * @return \DateTimeInterface
     */
    public function getExpiresAt(): \DateTimeInterface;

    /**
     * Check if the log has expired
     *
     * @return bool
     */
    public function isExpired(): bool;
}
