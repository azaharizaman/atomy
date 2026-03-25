<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Services;

use Nexus\ApprovalOperations\Contracts\ApprovalTemplateQueryInterface;
use Nexus\ApprovalOperations\Contracts\ApprovalTemplateResolverInterface;
use Nexus\ApprovalOperations\DTOs\ApprovalSubjectRef;
use Nexus\ApprovalOperations\DTOs\ApprovalTemplateReadModel;
use Nexus\ApprovalOperations\Exceptions\ApprovalTemplateNotFoundException;

final readonly class ApprovalTemplateResolver implements ApprovalTemplateResolverInterface
{
    public function __construct(
        private ApprovalTemplateQueryInterface $templates,
    ) {
    }

    public function resolve(string $tenantId, ApprovalSubjectRef $subject): ApprovalTemplateReadModel
    {
        $template = $this->templates->findBySubjectType($tenantId, $subject->subjectType);
        if ($template === null) {
            throw ApprovalTemplateNotFoundException::forSubjectType($tenantId, $subject->subjectType);
        }

        return $template;
    }
}
