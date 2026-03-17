<?php

declare(strict_types=1);

namespace App\Services\Project;

use App\Models\Project as ProjectModel;
use Nexus\Project\Contracts\ProjectPersistInterface;
use Nexus\Project\ValueObjects\ProjectSummary;
use Nexus\Tenant\Contracts\TenantContextInterface;

final readonly class AtomyProjectPersist implements ProjectPersistInterface
{
    public function __construct(private TenantContextInterface $tenantContext)
    {
    }

    public function persist(ProjectSummary $project): void
    {
        $tenantId = $this->tenantContext->requireTenant();
        $attrs = [
            'tenant_id' => $tenantId,
            'name' => $project->name,
            'client_id' => $project->clientId,
            'start_date' => $project->startDate->format('Y-m-d'),
            'end_date' => $project->endDate->format('Y-m-d'),
            'project_manager_id' => $project->projectManagerId,
            'status' => $project->status->value,
            'budget_type' => $project->budgetType,
            'completion_percentage' => $project->completionPercentage,
        ];
        ProjectModel::query()->updateOrCreate(
            ['id' => $project->id, 'tenant_id' => $tenantId],
            $attrs
        );
    }
}
