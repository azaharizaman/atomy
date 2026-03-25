<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Contracts;

use Nexus\ApprovalOperations\DTOs\ApprovalSubjectRef;
use Nexus\ApprovalOperations\DTOs\ApprovalTemplateReadModel;

interface ApprovalTemplateResolverInterface
{
    public function resolve(string $tenantId, ApprovalSubjectRef $subject): ApprovalTemplateReadModel;
}
