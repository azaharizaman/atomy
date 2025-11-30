<?php

declare(strict_types=1);

namespace Nexus\Payable\Exceptions;

/**
 * Matching failed exception.
 */
class MatchingFailedException extends PayableException
{
    public static function forBill(string $billId, string $reason): self
    {
        return new self("3-way matching failed for bill '{$billId}': {$reason}");
    }

    public static function varianceExceedsTolerance(string $billId): self
    {
        return new self("Bill '{$billId}' has variances exceeding configured tolerance.");
    }

    public static function missingPurchaseOrder(string $billId): self
    {
        return new self("Cannot match bill '{$billId}': No purchase order reference found.");
    }

    public static function missingGoodsReceipt(string $billId): self
    {
        return new self("Cannot match bill '{$billId}': No goods received note found.");
    }
}
