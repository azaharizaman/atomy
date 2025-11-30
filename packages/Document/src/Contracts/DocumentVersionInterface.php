<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

/**
 * Document version history interface.
 *
 * Tracks changes to a document over its lifetime, preserving
 * complete version history for audit and rollback purposes.
 */
interface DocumentVersionInterface
{
    /**
     * Get the unique version identifier (ULID).
     */
    public function getId(): string;

    /**
     * Get the parent document identifier.
     */
    public function getDocumentId(): string;

    /**
     * Get the version number (1-based sequential).
     */
    public function getVersionNumber(): int;

    /**
     * Get the storage path for this specific version.
     *
     * Format: {tenantId}/{year}/{month}/{uuid}/v{version}.{extension}
     */
    public function getStoragePath(): string;

    /**
     * Get the change description/summary for this version.
     */
    public function getChangeDescription(): ?string;

    /**
     * Get the identifier of the user who created this version.
     */
    public function getCreatedBy(): string;

    /**
     * Get the SHA-256 checksum for this version.
     */
    public function getChecksum(): string;

    /**
     * Get the file size in bytes for this version.
     */
    public function getFileSize(): int;

    /**
     * Get the version creation timestamp.
     */
    public function getCreatedAt(): \DateTimeInterface;

    /**
     * Convert the version to an array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
