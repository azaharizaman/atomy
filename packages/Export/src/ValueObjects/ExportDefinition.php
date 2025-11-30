<?php

declare(strict_types=1);

namespace Nexus\Export\ValueObjects;

/**
 * Export definition value object
 * 
 * Standardized intermediate representation for all exports.
 * Domain-agnostic, format-agnostic, validated structure.
 * 
 * This is the core data structure that flows through the export pipeline:
 * Domain Data → ExportDefinition → Formatter → Output
 */
final readonly class ExportDefinition
{
    /**
     * @param ExportMetadata $metadata Export metadata (title, author, etc.)
     * @param array<mixed> $structure Export structure (sections, tables, etc.)
     * @param array<string, mixed> $formatHints Format-specific rendering hints
     */
    public function __construct(
        public ExportMetadata $metadata,
        public array $structure,
        public array $formatHints = [],
    ) {}

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            metadata: ExportMetadata::fromArray($data['metadata'] ?? []),
            structure: $data['structure'] ?? [],
            formatHints: $data['format_hints'] ?? [],
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->metadata->schemaVersion,
            'metadata' => $this->metadata->toArray(),
            'structure' => $this->structure,
            'format_hints' => $this->formatHints,
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode(
            $this->toArray(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }

    /**
     * Create from JSON
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return self::fromArray($data);
    }

    /**
     * Get schema version
     */
    public function getSchemaVersion(): string
    {
        return $this->metadata->schemaVersion;
    }

    /**
     * Check if definition has security settings
     */
    public function isSecured(): bool
    {
        return $this->metadata->isSecured();
    }

    /**
     * Get structure item count
     */
    public function getStructureCount(): int
    {
        return count($this->structure);
    }
}
