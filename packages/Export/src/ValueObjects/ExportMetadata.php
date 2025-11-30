<?php

declare(strict_types=1);

namespace Nexus\Export\ValueObjects;

/**
 * Export metadata value object
 * 
 * Contains metadata about the export: title, author, timestamps,
 * security settings, watermarks, and schema version.
 */
final readonly class ExportMetadata
{
    /**
     * @param string $title Export title/document name
     * @param string $author User or system that generated export
     * @param \DateTimeImmutable $generatedAt Generation timestamp
     * @param string $schemaVersion Schema version (e.g., '1.0')
     * @param string|null $watermark Watermark text (e.g., 'DRAFT', 'CONFIDENTIAL')
     * @param array<string, mixed> $security Security settings (password, encryption)
     * @param array<string, mixed> $custom Custom metadata fields
     */
    public function __construct(
        public string $title,
        public string $author,
        public \DateTimeImmutable $generatedAt,
        public string $schemaVersion = '1.0',
        public ?string $watermark = null,
        public array $security = [],
        public array $custom = [],
    ) {}

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? 'Untitled Export',
            author: $data['author'] ?? 'system',
            generatedAt: isset($data['generated_at']) 
                ? new \DateTimeImmutable($data['generated_at'])
                : new \DateTimeImmutable(),
            schemaVersion: $data['schema_version'] ?? '1.0',
            watermark: $data['watermark'] ?? null,
            security: $data['security'] ?? [],
            custom: $data['custom'] ?? [],
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'author' => $this->author,
            'generated_at' => $this->generatedAt->format('Y-m-d\TH:i:s\Z'),
            'schema_version' => $this->schemaVersion,
            'watermark' => $this->watermark,
            'security' => $this->security,
            'custom' => $this->custom,
        ];
    }

    /**
     * Check if export has security settings
     */
    public function isSecured(): bool
    {
        return !empty($this->security);
    }

    /**
     * Check if export has watermark
     */
    public function hasWatermark(): bool
    {
        return $this->watermark !== null;
    }
}
