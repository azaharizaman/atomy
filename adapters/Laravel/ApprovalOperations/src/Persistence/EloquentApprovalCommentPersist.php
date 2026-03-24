<?php

declare(strict_types=1);

namespace Nexus\Laravel\ApprovalOperations\Persistence;

use Nexus\ApprovalOperations\Contracts\ApprovalCommentPersistInterface;
use Nexus\Laravel\ApprovalOperations\Models\OperationalApprovalComment;
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
