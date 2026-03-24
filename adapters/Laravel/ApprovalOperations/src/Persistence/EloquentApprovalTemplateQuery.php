<?php

declare(strict_types=1);

namespace Nexus\Laravel\ApprovalOperations\Persistence;

use Nexus\ApprovalOperations\Contracts\ApprovalTemplateQueryInterface;
use Nexus\ApprovalOperations\DTOs\ApprovalTemplateReadModel;
use Nexus\Laravel\ApprovalOperations\Models\OperationalApprovalTemplate;

final readonly class EloquentApprovalTemplateQuery implements ApprovalTemplateQueryInterface
{
    public function findBySubjectType(string $tenantId, string $subjectType): ?ApprovalTemplateReadModel
    {
        $row = OperationalApprovalTemplate::query()
            ->where('tenant_id', $tenantId)
            ->where('subject_type', $subjectType)
            ->first();

        if ($row === null) {
            return null;
        }

        return new ApprovalTemplateReadModel(
            id: (string) $row->id,
            tenantId: (string) $row->tenant_id,
            subjectType: (string) $row->subject_type,
            workflowDefinitionId: (string) $row->workflow_definition_id,
            policyId: (string) $row->policy_id,
            policyVersion: (string) $row->policy_version,
            templateVersion: (int) $row->template_version,
        );
    }
}
