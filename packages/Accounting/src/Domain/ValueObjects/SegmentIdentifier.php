<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\ValueObjects;

/**
 * Represents a segment identifier for segment reporting.
 */
final readonly class SegmentIdentifier
{
    public function __construct(
        private string $code,
        private string $name,
        private string $type,
        private ?string $parentCode = null
    ) {}

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getParentCode(): ?string
    {
        return $this->parentCode;
    }

    /**
     * Check if this segment has a parent.
     */
    public function hasParent(): bool
    {
        return $this->parentCode !== null;
    }

    /**
     * Create a geographic segment.
     */
    public static function geographic(string $code, string $name, ?string $parentCode = null): self
    {
        return new self($code, $name, 'geographic', $parentCode);
    }

    /**
     * Create a business segment.
     */
    public static function business(string $code, string $name, ?string $parentCode = null): self
    {
        return new self($code, $name, 'business', $parentCode);
    }

    /**
     * Create a product segment.
     */
    public static function product(string $code, string $name, ?string $parentCode = null): self
    {
        return new self($code, $name, 'product', $parentCode);
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'parent_code' => $this->parentCode,
        ];
    }
}
