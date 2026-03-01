<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Contracts;

use Nexus\Common\ValueObjects\Money;

/**
 * Interface for financial liability booking related to loyalty points.
 * Compliance with IFRS 15/ASC 606.
 */
interface AccountingIntegrationInterface
{
    /**
     * Book financial liability for newly accrued points.
     *
     * @param string $memberId The ID of the loyalty member.
     * @param int $points Accrued points count.
     * @param Money $fairValue The financial fair value for those points.
     * @param string $sourceTransactionId Reference to original transaction.
     * @return string Reference ID for the booked liability entry.
     */
    public function bookLiability(
        string $memberId,
        int $points,
        Money $fairValue,
        string $sourceTransactionId
    ): string;

    /**
     * Reclaim financial liability for points being retired (e.g., expiry or write-off).
     *
     * @param string $memberId The ID of the loyalty member.
     * @param int $points Expired points count.
     * @param Money $fairValue Financial value to write off.
     * @param string $reasonCode Reason for write-off (e.g., 'EXPIRY').
     * @return string Reference ID for the write-off entry.
     */
    public function bookWriteOff(
        string $memberId,
        int $points,
        Money $fairValue,
        string $reasonCode
    ): string;
}
