<?php

declare(strict_types=1);

namespace Nexus\Laravel\ApprovalOperations\Persistence;

use Nexus\ApprovalOperations\Contracts\ApprovalCommentPersistInterface;
use Nexus\ApprovalOperations\Exceptions\OperationalApprovalNotFoundException;
use Nexus\Laravel\ApprovalOperations\Models\OperationalApprovalComment;
use Nexus\Laravel\ApprovalOperations\Models\OperationalApprovalInstance;
use Illuminate\Support\Str;

final readonly class EloquentApprovalCommentPersist implements ApprovalCommentPersistInterface
{
    public function append(
        string $tenantId,
        string $instanceId,
        string $authorPrincipalId,
        string $body,
        ?string $attachmentStorageKey,
    ): void {
        $exists = OperationalApprovalInstance::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $instanceId)
            ->exists();
        if (!$exists) {
            throw OperationalApprovalNotFoundException::forInstance($instanceId);
        }

        OperationalApprovalComment::query()->create([
            'id' => (string) Str::ulid(),
            'tenant_id' => $tenantId,
            'instance_id' => $instanceId,
            'author_principal_id' => $authorPrincipalId,
            'body' => $body,
            'attachment_storage_key' => $attachmentStorageKey,
        ]);
    }
}
