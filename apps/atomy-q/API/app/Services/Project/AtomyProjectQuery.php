<?php

declare(strict_types=1);

namespace App\Services\Project;

use App\Models\Project as ProjectModel;
use Nexus\Project\Contracts\ProjectQueryInterface;
use Nexus\Project\Enums\ProjectStatus;
use Nexus\Project\ValueObjects\ProjectSummary;
use Nexus\Tenant\Contracts\TenantContextInterface;

final readonly class AtomyProjectQuery implements ProjectQueryInterface
{
    public function __construct(private TenantContextInterface $tenantContext)
    {
    }

    public function getById(string $projectId): ?ProjectSummary
    {
        $tenantId = $this->tenantContext->requireTenant();
        $row = ProjectModel::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $projectId)
            ->first();

        if ($row === null) {
            return null;
        }

        return new ProjectSummary(
            id: $row->id,
            name: $row->name,
            clientId: $row->client_id,
            startDate: $row->start_date->toDateTimeImmutable(),
            endDate: $row->end_date->toDateTimeImmutable(),
            projectManagerId: $row->project_manager_id,
            status: ProjectStatus::from($row->status),
            budgetType: $row->budget_type,
            completionPercentage: (float) $row->completion_percentage,
        );
    }

    public function getByClient(string $clientId): array
    {
        $tenantId = $this->tenantContext->requireTenant();
        return ProjectModel::query()
            ->where('tenant_id', $tenantId)
            ->where('client_id', $clientId)
            ->get()
            ->map(fn (ProjectModel $row) => new ProjectSummary(
                id: $row->id,
                name: $row->name,
                clientId: $row->client_id,
                startDate: $row->start_date->toDateTimeImmutable(),
                endDate: $row->end_date->toDateTimeImmutable(),
                projectManagerId: $row->project_manager_id,
                status: ProjectStatus::from($row->status),
                budgetType: $row->budget_type,
                completionPercentage: (float) $row->completion_percentage,
            ))
            ->values()
            ->all();
    }
}
