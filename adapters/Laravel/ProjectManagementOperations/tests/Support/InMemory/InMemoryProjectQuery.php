<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory;

use Nexus\Project\Contracts\ProjectQueryInterface;
use Nexus\Project\ValueObjects\ProjectSummary;
use Nexus\Project\Enums\ProjectStatus;

/**
 * In-memory L1 ProjectQueryInterface for integration tests.
 */
final class InMemoryProjectQuery implements ProjectQueryInterface
{
    /** @var array<string, ProjectSummary> */
    private array $projects = [];

    public function add(ProjectSummary $project): void
    {
        $this->projects[$project->id] = $project;
    }

    public function getById(string $projectId): ?ProjectSummary
    {
        return $this->projects[$projectId] ?? null;
    }

    /** @return list<ProjectSummary> */
    public function getByClient(string $clientId): array
    {
        $out = [];
        foreach ($this->projects as $p) {
            if ($p->clientId === $clientId) {
                $out[] = $p;
            }
        }
        return $out;
    }
}
