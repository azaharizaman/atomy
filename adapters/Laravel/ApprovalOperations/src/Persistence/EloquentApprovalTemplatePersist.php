<?php

declare(strict_types=1);

namespace Nexus\Laravel\ApprovalOperations\Persistence;

use Nexus\ApprovalOperations\Contracts\ApprovalTemplatePersistInterface;
use Nexus\ApprovalOperations\DTOs\ApprovalTemplateReadModel;
use Nexus\Laravel\ApprovalOperations\Models\OperationalApprovalTemplate;

final readonly class EloquentApprovalTemplatePersist implements ApprovalTemplatePersistInterface
{
    public function save(ApprovalTemplateReadModel $template): void
    {
        OperationalApprovalTemplate::query()->updateOrCreate(
            [
                'id' => $template->id,
            ],
            [
                'tenant_id' => $template->tenantId,
                'subject_type' => $template->subjectType,
                'workflow_definition_id' => $template->workflowDefinitionId,
                'policy_id' => $template->policyId,
                'policy_version' => $template->policyVersion,
                'template_version' => $template->templateVersion,
            ],
        );
    }
}
