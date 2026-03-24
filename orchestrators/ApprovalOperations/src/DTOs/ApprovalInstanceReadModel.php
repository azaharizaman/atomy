<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\DTOs;

/**
 * Read model for a persisted operational approval instance.
 */
final readonly class ApprovalInstanceReadModel
{
    public function __construct(
        public string $id,
        public string $tenantId,
        public string $templateId,
        public string $workflowInstanceId,
        public ApprovalSubjectRef $subject,
        public string $status,
    ) {
    }
}
