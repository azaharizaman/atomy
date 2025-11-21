<?php

declare(strict_types=1);

namespace Nexus\Audit\Contracts;

/**
 * Immutable Audit Record Interface
 * 
 * Represents a single, cryptographically-verified audit record in the hash chain.
 * Once created, records CANNOT be modified or deleted (SEC-AUD-0486).
 */
interface AuditRecordInterface
{
    /**
     * Get unique record identifier (ULID)
     */
    public function getId(): string;

    /**
     * Get tenant identifier for isolation
     * Satisfies: SEC-AUD-0487
     */
    public function getTenantId(): string;

    /**
     * Get sequence number (monotonic, per-tenant)
     * Satisfies: REL-AUD-0301
     */
    public function getSequenceNumber(): int;

    /**
     * Get type/category of audit record
     * Examples: 'user_role_assigned', 'payment_processed', 'document_deleted'
     */
    public function getRecordType(): string;

    /**
     * Get human-readable description
     */
    public function getDescription(): string;

    /**
     * Get entity type being acted upon (nullable for system events)
     */
    public function getSubjectType(): ?string;

    /**
     * Get entity ID being acted upon
     */
    public function getSubjectId(): ?string;

    /**
     * Get entity type performing action (nullable for system events)
     */
    public function getCauserType(): ?string;

    /**
     * Get entity ID performing action
     */
    public function getCauserId(): ?string;

    /**
     * Get additional properties (before/after state, metadata)
     * WARNING: Contains raw, unmasked data for forensic analysis
     */
    public function getProperties(): array;

    /**
     * Get audit severity level (1-4)
     */
    public function getLevel(): int;

    /**
     * Get hash of previous record in chain
     * Satisfies: SEC-AUD-0490
     */
    public function getPreviousHash(): ?string;

    /**
     * Get hash of this record
     * Calculated from: tenant_id + sequence_number + record_type + 
     *                  subject_type + subject_id + causer_type + causer_id + 
     *                  properties + level + previous_hash + created_at
     */
    public function getRecordHash(): string;

    /**
     * Get optional digital signature (Ed25519)
     * Used for non-repudiation in high-compliance environments
     */
    public function getSignature(): ?string;

    /**
     * Get identifier of signing entity (user, system process)
     */
    public function getSignedBy(): ?string;

    /**
     * Get creation timestamp
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get expiration date based on retention policy
     */
    public function getExpiresAt(): \DateTimeImmutable;

    /**
     * Check if record has expired
     */
    public function isExpired(): bool;
}
