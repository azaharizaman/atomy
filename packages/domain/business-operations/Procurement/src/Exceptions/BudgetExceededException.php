<?php

declare(strict_types=1);

namespace Nexus\Procurement\Exceptions;

/**
 * Exception thrown when budget limits are exceeded.
 */
class BudgetExceededException extends ProcurementException
{
    public static function poExceedsRequisition(string $poId, float $poAmount, float $reqAmount, float $threshold): self
    {
        $exceededBy = (($poAmount - $reqAmount) / $reqAmount) * 100;
        return new self(
            "PO '{$poId}' amount ({$poAmount}) exceeds requisition amount ({$reqAmount}) by {$exceededBy}%. " .
            "Maximum allowed variance is {$threshold}%."
        );
    }

    public static function blanketPoReleaseExceedsTotal(string $poId, float $releaseAmount, float $totalCommitted): self
    {
        return new self(
            "Blanket PO '{$poId}' release amount ({$releaseAmount}) exceeds total committed value ({$totalCommitted})."
        );
    }
}
