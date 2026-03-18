<?php

declare(strict_types=1);

namespace App\Services\Project;

use Nexus\Project\Contracts\ProjectManagerInterface;
use Nexus\Project\Contracts\ProjectQueryInterface as ProjectDomainQueryInterface;
use Nexus\Project\ValueObjects\ProjectSummary;

final readonly class ProjectService
{
    public function __construct(
        private ProjectManagerInterface $manager,
        private ProjectDomainQueryInterface $query,
    ) {
    }

    public function create(ProjectSummary $summary): void
    {
        $this->manager->create($summary);
    }

    public function update(ProjectSummary $summary): void
    {
        $this->manager->update($summary);
    }

    public function findById(string $id): ?ProjectSummary
    {
        return $this->query->getById($id);
    }
}

