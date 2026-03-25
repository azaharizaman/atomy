<?php

declare(strict_types=1);

namespace Nexus\Laravel\ApprovalOperations\Persistence;

use Nexus\ApprovalOperations\Contracts\ApprovalInstanceQueryInterface;
use Nexus\ApprovalOperations\DTOs\ApprovalInstancePageReadModel;
use Nexus\ApprovalOperations\DTOs\ApprovalInstanceReadModel;
use Nexus\ApprovalOperations\DTOs\ApprovalSubjectRef;
use Nexus\ApprovalOperations\Enums\ApprovalStatus;
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

        return $this->hydrate($row);
    }

    public function findByTenant(string $tenantId, int $page = 1, int $perPage = 25): ApprovalInstancePageReadModel
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $query = OperationalApprovalInstance::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at');

        $total = (clone $query)->count();
        $items = $query
            ->forPage($page, $perPage)
            ->get()
            ->map(fn (OperationalApprovalInstance $row): ApprovalInstanceReadModel => $this->hydrate($row))
            ->all();

        return new ApprovalInstancePageReadModel(
            items: $items,
            total: $total,
            perPage: $perPage,
            currentPage: $page,
            lastPage: max(1, (int) \ceil($total / $perPage)),
        );
    }

    private function hydrate(OperationalApprovalInstance $row): ApprovalInstanceReadModel
    {
        $wf = $row->workflow_instance_id;
        $dueAt = null;
        $createdAt = null;
        if ($row->due_at instanceof \DateTimeInterface) {
            $dueAt = \DateTimeImmutable::createFromInterface($row->due_at);
        } elseif (\is_string($row->due_at) && \trim($row->due_at) !== '') {
            try {
                $dueAt = new \DateTimeImmutable($row->due_at, new \DateTimeZone('UTC'));
            } catch (\Throwable) {
                $dueAt = null;
            }
        }
        if ($row->created_at instanceof \DateTimeInterface) {
            $createdAt = \DateTimeImmutable::createFromInterface($row->created_at);
        } elseif (\is_string($row->created_at) && \trim($row->created_at) !== '') {
            try {
                $createdAt = new \DateTimeImmutable($row->created_at, new \DateTimeZone('UTC'));
            } catch (\Throwable) {
                $createdAt = null;
            }
        }

        return new ApprovalInstanceReadModel(
            id: (string) $row->id,
            tenantId: (string) $row->tenant_id,
            templateId: (string) $row->template_id,
            workflowInstanceId: $wf === null ? null : (string) $wf,
            subject: new ApprovalSubjectRef((string) $row->subject_type, (string) $row->subject_id),
            status: ApprovalStatus::from((string) $row->status),
            dueAt: $dueAt,
            createdAt: $createdAt,
        );
    }
}
