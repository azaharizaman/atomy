<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Enums;

enum OperationalApprovalWorkflowMissingReason: string
{
    case InstanceCorrelationMissing = 'instance_correlation_missing';
    case WorkflowInstanceNotFound = 'workflow_instance_not_found';
}
