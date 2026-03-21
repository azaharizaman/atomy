<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\Enums;

enum DecisionOutcome: string
{
    case Allow = 'allow';
    case Deny = 'deny';
    case Approve = 'approve';
    case Reject = 'reject';
    case Escalate = 'escalate';
    case Route = 'route';
}
