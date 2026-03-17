<?php

declare(strict_types=1);

namespace App\Services\Project;

use App\Models\Project as ProjectModel;
use Nexus\Project\Contracts\ProjectQueryInterface;
use Nexus\Project\Enums\ProjectStatus;
use Nexus\Project\ValueObjects\ProjectSummary;

final class AtomyProjectQuery implements ProjectQueryInterface
{
    public function getById(string $projectId): ?ProjectSummary
    {
        $row = ProjectModel::query()->find($projectId);

        if ($row === null) {
            return null;
        }

        return new ProjectSummary(
            id: $row->id,
            name: $row->name,
            clientId: $row->client_id,
            startDate: \DateTimeImmutable::createFromInterface($row->start_date->toDateTimeImmutable()),
            endDate: \DateTimeImmutable::createFromInterface($row->end_date->toDateTimeImmutable()),
            projectManagerId: $row->project_manager_id,
            status: ProjectStatus::from($row->status),
            budgetType: $row->budget_type,
            completionPercentage: (float) $row->completion_percentage,
        );
    }

    public function getByClient(string $clientId): array
    {
        return ProjectModel::query()
            ->where('client_id', $clientId)
            ->get()
            ->map(fn (ProjectModel $row) => new ProjectSummary(
                id: $row->id,
                name: $row->name,
                clientId: $row->client_id,
                startDate: \DateTimeImmutable::createFromInterface($row->start_date->toDateTimeImmutable()),
                endDate: \DateTimeImmutable::createFromInterface($row->end_date->toDateTimeImmutable()),
                projectManagerId: $row->project_manager_id,
                status: ProjectStatus::from($row->status),
                budgetType: $row->budget_type,
                completionPercentage: (float) $row->completion_percentage,
            ))
            ->values()
            ->all();
    }
}
