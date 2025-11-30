<?php

declare(strict_types=1);

namespace Nexus\Export\ValueObjects;

/**
 * Export section value object
 * 
 * Represents a hierarchical section in the export definition.
 * Supports nesting up to 8 levels deep.
 */
final readonly class ExportSection
{
    /**
     * @param string $name Section name/title
     * @param int $level Nesting level (0-8)
     * @param array<mixed> $items Section items (lines, subsections, tables)
     * @param array<string> $styling Styling hints (['bold', 'underline'])
     * @param array<string, mixed> $metadata Additional section metadata
     */
    public function __construct(
        public string $name,
        public int $level,
        public array $items = [],
        public array $styling = [],
        public array $metadata = [],
    ) {
        if ($level < 0 || $level > 8) {
            throw new \InvalidArgumentException("Section level must be between 0 and 8, got {$level}");
        }
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? 'Untitled Section',
            level: $data['level'] ?? 0,
            items: $data['items'] ?? [],
            styling: $data['styling'] ?? [],
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'type' => 'section',
            'name' => $this->name,
            'level' => $this->level,
            'items' => $this->items,
            'styling' => $this->styling,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Check if section has items
     */
    public function hasItems(): bool
    {
        return !empty($this->items);
    }

    /**
     * Get item count
     */
    public function getItemCount(): int
    {
        return count($this->items);
    }
}
