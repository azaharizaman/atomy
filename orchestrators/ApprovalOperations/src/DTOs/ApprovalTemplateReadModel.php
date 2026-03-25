<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\DTOs;

/**
 * Read model for an approval template row (L3 may map from Eloquent).
 */
final readonly class ApprovalTemplateReadModel
{
    public function __construct(
        public string $id,
        public string $tenantId,
        public string $subjectType,
        public string $workflowDefinitionId,
        public string $policyId,
        public string $policyVersion,
        public int $templateVersion,
    ) {
    }
}
