<?php

declare(strict_types=1);

namespace Nexus\Period\ValueObjects;

/**
 * Period Metadata Value Object
 * 
 * Immutable representation of a period's descriptive metadata.
 */
final readonly class PeriodMetadata
{
    public function __construct(
        private string $name,
        private ?string $description = null
    ) {
        if (trim($this->name) === '') {
            throw new \InvalidArgumentException('Period name cannot be empty');
        }

        if (strlen($this->name) > 100) {
            throw new \InvalidArgumentException('Period name cannot exceed 100 characters');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function hasDescription(): bool
    {
        return $this->description !== null && trim($this->description) !== '';
    }

    public function withName(string $name): self
    {
        return new self($name, $this->description);
    }

    public function withDescription(?string $description): self
    {
        return new self($this->name, $description);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
