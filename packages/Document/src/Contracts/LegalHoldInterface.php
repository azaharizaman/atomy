<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

/**
 * Legal hold entity interface.
 *
 * Represents a legal hold applied to a document to prevent deletion
 * during litigation, regulatory investigation, or compliance requirements.
 *
 * Legal holds override retention policies and prevent document disposal
 * regardless of retention period expiration.
 */
interface LegalHoldInterface
{
    /**
     * Get the unique legal hold identifier (ULID).
     */
    public function getId(): string;

    /**
     * Get the tenant identifier for multi-tenancy isolation.
     */
    public function getTenantId(): string;

    /**
     * Get the document identifier under hold.
     */
    public function getDocumentId(): string;

    /**
     * Get the reason for the legal hold.
     */
    public function getReason(): string;

    /**
     * Get the legal matter reference (case number, investigation ID).
     */
    public function getMatterReference(): ?string;

    /**
     * Get the user who applied the hold.
     */
    public function getAppliedBy(): string;

    /**
     * Get the timestamp when hold was applied.
     */
    public function getAppliedAt(): \DateTimeInterface;

    /**
     * Get the user who released the hold (null if still active).
     */
    public function getReleasedBy(): ?string;

    /**
     * Get the timestamp when hold was released (null if still active).
     */
    public function getReleasedAt(): ?\DateTimeInterface;

    /**
     * Get the reason for releasing the hold (null if still active).
     */
    public function getReleaseReason(): ?string;

    /**
     * Check if the legal hold is currently active.
     */
    public function isActive(): bool;

    /**
     * Get optional expiration date for the hold (null = indefinite).
     *
     * Some holds may have a known end date (e.g., statute of limitations).
     */
    public function getExpiresAt(): ?\DateTimeInterface;

    /**
     * Get additional metadata about the hold.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Convert the legal hold to an array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
