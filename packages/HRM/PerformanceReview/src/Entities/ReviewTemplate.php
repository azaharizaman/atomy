<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\Entities;

final readonly class ReviewTemplate
{
    public function __construct(
        public string $id,
        public string $name,
        public array $sections,
        public bool $isActive = true,
    ) {}
}
