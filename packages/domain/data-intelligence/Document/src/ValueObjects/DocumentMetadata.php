<?php

declare(strict_types=1);

namespace Nexus\Document\ValueObjects;

/**
 * Document metadata value object.
 *
 * Encapsulates document metadata including original filename,
 * MIME type, file size, checksum, tags, and custom fields.
 * Immutable by design.
 */
final readonly class DocumentMetadata
{
    /**
     * @param string $originalFilename Original filename as uploaded
     * @param string $mimeType MIME type of the document
     * @param int $fileSize File size in bytes
     * @param string $checksum SHA-256 checksum for integrity
     * @param array<string> $tags Array of tag strings for classification
     * @param array<string, mixed> $customFields Flexible key-value metadata
     */
    public function __construct(
        public string $originalFilename,
        public string $mimeType,
        public int $fileSize,
        public string $checksum,
        public array $tags = [],
        public array $customFields = []
    ) {
        if ($fileSize < 0) {
            throw new \InvalidArgumentException('File size cannot be negative');
        }

        if (empty($originalFilename)) {
            throw new \InvalidArgumentException('Original filename cannot be empty');
        }

        if (empty($mimeType)) {
            throw new \InvalidArgumentException('MIME type cannot be empty');
        }

        if (strlen($checksum) !== 64) {
            throw new \InvalidArgumentException('Checksum must be 64 characters (SHA-256)');
        }
    }

    /**
     * Create from an array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            originalFilename: $data['original_filename'] ?? '',
            mimeType: $data['mime_type'] ?? '',
            fileSize: (int) ($data['file_size'] ?? 0),
            checksum: $data['checksum'] ?? '',
            tags: $data['tags'] ?? [],
            customFields: $data['custom_fields'] ?? []
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'original_filename' => $this->originalFilename,
            'mime_type' => $this->mimeType,
            'file_size' => $this->fileSize,
            'checksum' => $this->checksum,
            'tags' => $this->tags,
            'custom_fields' => $this->customFields,
        ];
    }

    /**
     * Check if the document has a specific tag.
     */
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags, true);
    }

    /**
     * Get a custom field value.
     */
    public function getCustomField(string $key, mixed $default = null): mixed
    {
        return $this->customFields[$key] ?? $default;
    }

    /**
     * Get the file extension from the original filename.
     */
    public function getExtension(): string
    {
        return pathinfo($this->originalFilename, PATHINFO_EXTENSION);
    }
}
