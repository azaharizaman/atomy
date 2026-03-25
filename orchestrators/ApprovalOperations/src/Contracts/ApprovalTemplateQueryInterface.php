<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Contracts;

use Nexus\ApprovalOperations\DTOs\ApprovalTemplateReadModel;

interface ApprovalTemplateQueryInterface
{
    public function findBySubjectType(string $tenantId, string $subjectType): ?ApprovalTemplateReadModel;
}
