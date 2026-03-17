<?php

declare(strict_types=1);

namespace App\Services\Project;

use App\Models\Project as ProjectModel;
use Nexus\Project\Contracts\ProjectPersistInterface;
use Nexus\Project\Enums\ProjectStatus;
use Nexus\Project\ValueObjects\ProjectSummary;

final class AtomyProjectPersist implements ProjectPersistInterface
{
    public function persist(ProjectSummary $project): void
    {
        $tenantId = request()?->attributes->get('auth_tenant_id');
        $attrs = [
            'name' => $project->name,
            'client_id' => $project->clientId,
            'start_date' => $project->startDate->format('Y-m-d'),
            'end_date' => $project->endDate->format('Y-m-d'),
            'project_manager_id' => $project->projectManagerId,
            'status' => $project->status->value,
            'budget_type' => $project->budgetType,
            'completion_percentage' => $project->completionPercentage,
        ];
        if ($tenantId !== null && $tenantId !== '') {
            $attrs['tenant_id'] = $tenantId;
        }
        ProjectModel::query()->updateOrCreate(
            ['id' => $project->id],
            $attrs
        );
    }
}
