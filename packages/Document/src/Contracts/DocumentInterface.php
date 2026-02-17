<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

use Nexus\Document\ValueObjects\DocumentState;
use Nexus\Document\ValueObjects\DocumentType;

/**
 * Primary document metadata interface.
 *
 * Represents a document entity with its metadata, storage information,
 * and integrity verification details. All documents are tenant-scoped
 * and owner-scoped for multi-tenancy and access control.
 */
interface DocumentInterface
{
    /**
     * Get the unique document identifier (ULID).
     */
    public function getId(): string;

    /**
     * Get the tenant identifier for multi-tenancy isolation.
     */
    public function getTenantId(): string;

    /**
     * Get the owner identifier (user who uploaded/owns the document).
     */
    public function getOwnerId(): string;

    /**
     * Get the document type classification.
     */
    public function getType(): DocumentType;

    /**
     * Get the current document state.
     */
    public function getState(): DocumentState;

    /**
     * Get the storage path (S3 key or file system path).
     *
     * Format: {tenantId}/{year}/{month}/{uuid}/v{version}.{extension}
     */
    public function getStoragePath(): string;

    /**
     * Get the SHA-256 checksum for integrity verification.
     */
    public function getChecksum(): string;

    /**
     * Get the MIME type of the document.
     */
    public function getMimeType(): string;

    /**
     * Get the file size in bytes.
     */
    public function getFileSize(): int;

    /**
     * Get the original filename as uploaded by the user.
     */
    public function getOriginalFilename(): string;

    /**
     * Get the current version number.
     */
    public function getVersion(): int;

    /**
     * Get the metadata as an associative array.
     *
     * Contains tags, custom fields, and other flexible metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Get the document creation timestamp.
     */
    public function getCreatedAt(): \DateTimeInterface;

    /**
     * Get the document last update timestamp.
     */
    public function getUpdatedAt(): \DateTimeInterface;

    /**
     * Get the document soft deletion timestamp (null if not deleted).
     */
    public function getDeletedAt(): ?\DateTimeInterface;

    /**
     * Check if the document is soft deleted.
     */
    public function isDeleted(): bool;

    /**
     * Set the current document state.
     */
    public function setState(DocumentState $state): void;

    /**
     * Set the current version number.
     */
    public function setVersion(int $version): void;

    /**
     * Set the storage path.
     */
    public function setStoragePath(string $path): void;

    /**
     * Set the checksum.
     */
    public function setChecksum(string $checksum): void;

    /**
     * Convert the document to an array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
