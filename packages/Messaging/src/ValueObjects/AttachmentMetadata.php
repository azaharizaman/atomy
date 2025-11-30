<?php

declare(strict_types=1);

namespace Nexus\Messaging\ValueObjects;

/**
 * Attachment metadata (references only, no file I/O)
 * 
 * Stores reference to attachment without handling actual file storage.
 * File storage is delegated to Nexus\Storage or application layer.
 * 
 * @package Nexus\Messaging
 */
final readonly class AttachmentMetadata
{
    /**
     * @param string $filename Original filename
     * @param string $mimeType MIME type (e.g., 'application/pdf')
     * @param int $sizeBytes File size in bytes
     * @param string|null $storageReference Reference to file in storage system (e.g., S3 key, document ID)
     * @param string|null $url Public URL to access file (if applicable)
     */
    public function __construct(
        public string $filename,
        public string $mimeType,
        public int $sizeBytes,
        public ?string $storageReference = null,
        public ?string $url = null,
    ) {
        if (empty($this->filename)) {
            throw new \InvalidArgumentException('Filename cannot be empty');
        }

        if ($this->sizeBytes < 0) {
            throw new \InvalidArgumentException('Size cannot be negative');
        }
    }

    /**
     * Create from array representation
     */
    public static function fromArray(array $data): self
    {
        return new self(
            filename: $data['filename'],
            mimeType: $data['mime_type'],
            sizeBytes: $data['size_bytes'],
            storageReference: $data['storage_reference'] ?? null,
            url: $data['url'] ?? null,
        );
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'filename' => $this->filename,
            'mime_type' => $this->mimeType,
            'size_bytes' => $this->sizeBytes,
            'storage_reference' => $this->storageReference,
            'url' => $this->url,
        ];
    }

    /**
     * Get human-readable file size
     */
    public function getHumanReadableSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->sizeBytes;
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
