<?php

declare(strict_types=1);

namespace App\Exception;

class ComparisonRunNotPendingApprovalException extends \DomainException
{
    public static function forId(string $runId): self
    {
        return new self(sprintf('Comparison run "%s" is not pending approval.', $runId));
    }
}
