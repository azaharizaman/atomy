<?php

declare(strict_types=1);

namespace Nexus\Project\Contracts;

use Nexus\Project\ValueObjects\ProjectSummary;

/**
 * Project lifecycle (FUN-PRO-0564). BUS-PRO-0042: project MUST have a project manager assigned.
 */
interface ProjectManagerInterface
{
    /**
     * Create a new project.
     */
    public function create(ProjectSummary $project): void;

    /**
     * Update project. Validates BUS-PRO-0096 when status is completed (no incomplete tasks).
     */
    public function update(ProjectSummary $project): void;
}
