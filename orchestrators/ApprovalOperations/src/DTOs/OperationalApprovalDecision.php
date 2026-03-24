<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\DTOs;

enum OperationalApprovalDecision: string
{
    case Approve = 'approve';
    case Reject = 'reject';
}
