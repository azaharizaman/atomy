<?php

declare(strict_types=1);

namespace App\Services\Project;

use App\Models\Task;
use Nexus\Project\Contracts\IncompleteTaskCountInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;

final readonly class AtomyIncompleteTaskCount implements IncompleteTaskCountInterface
{
    public function __construct(private TenantContextInterface $tenantContext)
    {
    }

    public function getIncompleteTaskCount(string $projectId): int
    {
        $tenantId = $this->tenantContext->requireTenant();
        return Task::query()
            ->where('tenant_id', $tenantId)
            ->where('project_id', $projectId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
    }
}
