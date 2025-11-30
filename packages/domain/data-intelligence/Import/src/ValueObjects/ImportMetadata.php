<?php

declare(strict_types=1);

namespace Nexus\Import\ValueObjects;

/**
 * Immutable import metadata value object
 * 
 * Contains contextual information about the import operation.
 */
readonly class ImportMetadata
{
    /**
     * @param string $originalFileName Original filename
     * @param int $fileSize File size in bytes
     * @param string $mimeType MIME type
     * @param \DateTimeImmutable $uploadedAt Upload timestamp
     * @param string|null $uploadedBy User identifier (nullable)
     * @param string|null $tenantId Tenant identifier (null for system imports)
     */
    public function __construct(
        public string $originalFileName,
        public int $fileSize,
        public string $mimeType,
        public \DateTimeImmutable $uploadedAt,
        public ?string $uploadedBy = null,
        public ?string $tenantId = null
    ) {}

    /**
     * Get file size in human-readable format
     */
    public function getFormattedFileSize(): string
    {
        $bytes = $this->fileSize;
        
        if ($bytes < 1024) {
            return "{$bytes} B";
        }
        
        if ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        }
        
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        
        return round($bytes / 1073741824, 2) . ' GB';
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'file_name' => $this->originalFileName,
            'file_size_bytes' => $this->fileSize,
            'file_size_formatted' => $this->getFormattedFileSize(),
            'mime_type' => $this->mimeType,
            'uploaded_at' => $this->uploadedAt->format(\DateTimeInterface::ATOM),
            'uploaded_by' => $this->uploadedBy,
            'tenant_id' => $this->tenantId,
        ];
    }
}
