<?php

declare(strict_types=1);

namespace Nexus\Laravel\ApprovalOperations\Persistence;

use Nexus\ApprovalOperations\Contracts\ApprovalInstanceQueryInterface;
use Nexus\ApprovalOperations\DTOs\ApprovalInstanceReadModel;
use Nexus\ApprovalOperations\DTOs\ApprovalSubjectRef;
use Nexus\Laravel\ApprovalOperations\Models\OperationalApprovalInstance;

final readonly class EloquentApprovalInstanceQuery implements ApprovalInstanceQueryInterface
{
    public function findById(string $tenantId, string $instanceId): ?ApprovalInstanceReadModel
    {
        $row = OperationalApprovalInstance::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $instanceId)
            ->first();

        if ($row === null) {
            return null;
        }

        $wf = $row->workflow_instance_id;

        return new ApprovalInstanceReadModel(
            id: (string) $row->id,
            tenantId: (string) $row->tenant_id,
            templateId: (string) $row->template_id,
            workflowInstanceId: $wf === null ? null : (string) $wf,
            subject: new ApprovalSubjectRef((string) $row->subject_type, (string) $row->subject_id),
            status: (string) $row->status,
        );
    }
}
