<?php

declare(strict_types=1);

namespace Nexus\Content\ValueObjects;

use Nexus\Content\Exceptions\InvalidCategoryException;

/**
 * Hierarchical article category value object
 * 
 * Supports up to 3 levels of hierarchy for organizing content.
 * 
 * @property-read string $categoryId
 * @property-read string $name
 * @property-read string $slug
 * @property-read string|null $parentCategoryId
 * @property-read int $level Category depth (1-3)
 * @property-read string|null $description
 * @property-read array<string, mixed> $metadata
 */
final readonly class ArticleCategory
{
    private const MAX_DEPTH = 3;

    /**
     * @param string $categoryId
     * @param string $name
     * @param string $slug
     * @param string|null $parentCategoryId
     * @param int $level
     * @param string|null $description
     * @param array<string, mixed> $metadata
     */
    private function __construct(
        public string $categoryId,
        public string $name,
        public string $slug,
        public ?string $parentCategoryId,
        public int $level,
        public ?string $description = null,
        public array $metadata = [],
    ) {
        if (empty($this->categoryId)) {
            throw new InvalidCategoryException('Category ID cannot be empty');
        }

        if (empty(trim($this->name))) {
            throw new InvalidCategoryException('Category name cannot be empty');
        }

        if (empty(trim($this->slug))) {
            throw new InvalidCategoryException('Category slug cannot be empty');
        }

        if ($this->level < 1 || $this->level > self::MAX_DEPTH) {
            throw new InvalidCategoryException(
                sprintf('Category level must be between 1 and %d', self::MAX_DEPTH)
            );
        }

        if ($this->level > 1 && empty($this->parentCategoryId)) {
            throw new InvalidCategoryException('Parent category required for level > 1');
        }

        if ($this->level === 1 && $this->parentCategoryId !== null) {
            throw new InvalidCategoryException('Level 1 categories cannot have parent');
        }
    }

    /**
     * Create root level category
     */
    public static function createRoot(
        string $categoryId,
        string $name,
        string $slug,
        ?string $description = null,
        array $metadata = [],
    ): self {
        return new self(
            categoryId: $categoryId,
            name: $name,
            slug: $slug,
            parentCategoryId: null,
            level: 1,
            description: $description,
            metadata: $metadata,
        );
    }

    /**
     * Create child category
     */
    public static function createChild(
        string $categoryId,
        string $name,
        string $slug,
        string $parentCategoryId,
        int $parentLevel,
        ?string $description = null,
        array $metadata = [],
    ): self {
        $newLevel = $parentLevel + 1;

        if ($newLevel > self::MAX_DEPTH) {
            throw new InvalidCategoryException(
                sprintf('Cannot exceed maximum depth of %d levels', self::MAX_DEPTH)
            );
        }

        return new self(
            categoryId: $categoryId,
            name: $name,
            slug: $slug,
            parentCategoryId: $parentCategoryId,
            level: $newLevel,
            description: $description,
            metadata: $metadata,
        );
    }

    /**
     * Check if this is a root category
     */
    public function isRoot(): bool
    {
        return $this->level === 1;
    }

    /**
     * Check if this category can have children
     */
    public function canHaveChildren(): bool
    {
        return $this->level < self::MAX_DEPTH;
    }
}
