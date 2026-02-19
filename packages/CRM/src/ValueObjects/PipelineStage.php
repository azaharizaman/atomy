<?php

declare(strict_types=1);

namespace Nexus\CRM\ValueObjects;

use Nexus\CRM\Enums\OpportunityStage;

/**
 * Pipeline Stage Value Object
 * 
 * Represents a stage within a sales pipeline with its configuration.
 * Immutable value object for pipeline stage definition.
 * 
 * @package Nexus\CRM\ValueObjects
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class PipelineStage
{
    /**
     * @param string $name Stage name
     * @param int $position Position in the pipeline (1-based)
     * @param int $probability Default win probability percentage (0-100)
     * @param string|null $description Stage description
     * @param array<string, mixed> $metadata Additional stage configuration
     */
    public function __construct(
        public string $name,
        public int $position,
        public int $probability,
        public ?string $description = null,
        public array $metadata = []
    ) {
        if ($position < 1) {
            throw new \InvalidArgumentException('Stage position must be at least 1');
        }

        if ($probability < 0 || $probability > 100) {
            throw new \InvalidArgumentException('Probability must be between 0 and 100');
        }
    }

    /**
     * Create from OpportunityStage enum
     */
    public static function fromEnum(OpportunityStage $stage): self
    {
        return new self(
            name: $stage->label(),
            position: $stage->getPosition(),
            probability: $stage->getDefaultProbability()
        );
    }

    /**
     * Get stage name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get position in pipeline
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Get win probability percentage
     */
    public function getProbability(): int
    {
        return $this->probability;
    }

    /**
     * Get probability as decimal (0.0 - 1.0)
     */
    public function getProbabilityDecimal(): float
    {
        return $this->probability / 100;
    }

    /**
     * Get stage description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Get metadata value
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if this is a final stage (won/lost)
     */
    public function isFinal(): bool
    {
        return $this->probability === 100 || $this->probability === 0;
    }

    /**
     * Check if this is a winning stage
     */
    public function isWinStage(): bool
    {
        return $this->probability === 100;
    }

    /**
     * Check if this is a losing stage
     */
    public function isLossStage(): bool
    {
        return $this->probability === 0;
    }

    /**
     * Compare position with another stage
     */
    public function isBefore(self $other): bool
    {
        return $this->position < $other->position;
    }

    /**
     * Compare position with another stage
     */
    public function isAfter(self $other): bool
    {
        return $this->position > $other->position;
    }

    /**
     * Create a new stage with updated probability
     */
    public function withProbability(int $probability): self
    {
        return new self(
            name: $this->name,
            position: $this->position,
            probability: $probability,
            description: $this->description,
            metadata: $this->metadata
        );
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'position' => $this->position,
            'probability' => $this->probability,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ];
    }

    public function __toString(): string
    {
        return $this->name;
    }
}