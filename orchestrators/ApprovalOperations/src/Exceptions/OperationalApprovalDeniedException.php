<?php

declare(strict_types=1);

namespace Nexus\ApprovalOperations\Exceptions;

use Nexus\PolicyEngine\Domain\PolicyDecision;

/**
 * PolicyEngine denied or rejected starting the operational approval.
 */
final class OperationalApprovalDeniedException extends \RuntimeException
{
    public static function fromDecision(PolicyDecision $decision): self
    {
        return new self(\sprintf(
            'Operational approval denied by policy (trace %s).',
            $decision->traceId
        ));
    }
}
