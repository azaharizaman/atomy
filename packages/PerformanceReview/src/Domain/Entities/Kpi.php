<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\Domain\Entities;

use Nexus\PerformanceReview\Domain\ValueObjects\Rating;
use RuntimeException;

final class Kpi
{
    private ?Rating $rating = null;
    private ?string $managerComment = null;
    private ?string $selfComment = null;

    public function __construct(
        private readonly string $name,
        private readonly string $description,
        private readonly float $weight = 1.0,
        private readonly ?string $target = null
    ) {
        if ($weight < 0) {
            throw new RuntimeException("KPI weight cannot be negative.");
        }
    }

    public function evaluate(Rating $rating, ?string $comment = null): void
    {
        $this->rating = $rating;
        $this->managerComment = $comment;
    }

    public function selfAssess(?string $comment): void
    {
        $this->selfComment = $comment;
    }

    public function getRating(): ?Rating
    {
        return $this->rating;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isRated(): bool
    {
        return $this->rating !== null;
    }
}
