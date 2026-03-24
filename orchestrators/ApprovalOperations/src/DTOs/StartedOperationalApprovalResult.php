<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\DTOs;

/**
 * Result of successfully starting an operational approval.
 */
final readonly class StartedOperationalApprovalResult
{
    public function __construct(
        public string $instanceId,
        public string $workflowInstanceId,
    ) {
    }
}
