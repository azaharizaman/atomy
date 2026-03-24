<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Contracts;

interface ApprovalCommentPersistInterface
{
    public function append(
        string $tenantId,
        string $instanceId,
        string $authorPrincipalId,
        string $body,
        ?string $attachmentStorageKey,
    ): void;
}
