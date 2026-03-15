<?php

declare(strict_types=1);

namespace Nexus\Project\Contracts;

use Nexus\Project\ValueObjects\ProjectSummary;

/**
 * Project persistence (write side). CQRS split.
 */
interface ProjectPersistInterface
{
    public function persist(ProjectSummary $project): void;
}
