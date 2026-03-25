<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\DTOs;

use Nexus\ApprovalOperations\Enums\ApprovalStatus;

/**
 * Read model for a persisted operational approval instance.
 */
final readonly class ApprovalInstanceReadModel
{
    public function __construct(
        public string $id,
        public string $tenantId,
        public string $templateId,
        public ?string $workflowInstanceId,
        public ApprovalSubjectRef $subject,
        public ApprovalStatus $status,
        public ?\DateTimeImmutable $dueAt = null,
        public ?\DateTimeImmutable $createdAt = null,
    ) {
    }
}
