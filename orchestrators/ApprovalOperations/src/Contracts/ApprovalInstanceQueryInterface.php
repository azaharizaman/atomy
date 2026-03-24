<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Contracts;

use Nexus\ApprovalOperations\DTOs\ApprovalInstanceReadModel;

interface ApprovalInstanceQueryInterface
{
    public function findById(string $tenantId, string $instanceId): ?ApprovalInstanceReadModel;
}
