<?php

declare(strict_types=1);

namespace Nexus\Laravel\ApprovalOperations\Persistence;

use Nexus\ApprovalOperations\Contracts\ApprovalInstancePersistInterface;
use Nexus\ApprovalOperations\DTOs\ApprovalInstanceReadModel;
use Nexus\Laravel\ApprovalOperations\Models\OperationalApprovalInstance;

final readonly class EloquentApprovalInstancePersist implements ApprovalInstancePersistInterface
{
    public function save(ApprovalInstanceReadModel $instance): void
    {
        OperationalApprovalInstance::query()->updateOrCreate(
            [
                'id' => $instance->id,
                'tenant_id' => $instance->tenantId,
            ],
            [
                'tenant_id' => $instance->tenantId,
                'template_id' => $instance->templateId,
                'workflow_instance_id' => $instance->workflowInstanceId,
                'subject_type' => $instance->subject->subjectType,
                'subject_id' => $instance->subject->subjectId,
                'status' => $instance->status,
            ],
        );
    }
}
