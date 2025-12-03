<?php

declare(strict_types=1);

namespace Nexus\PerformanceReview\Contracts;

use Nexus\PerformanceReview\Entities\ReviewTemplate;

interface ReviewTemplateRepositoryInterface
{
    public function findById(string $id): ?ReviewTemplate;
    public function findActive(): array;
    public function save(ReviewTemplate $template): void;
}
