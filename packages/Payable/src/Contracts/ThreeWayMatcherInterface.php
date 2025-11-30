<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Interface for 3-way matching engine.
 *
 * Validates vendor bills against Purchase Orders and Goods Received Notes
 * with configurable per-vendor tolerance rules.
 */
interface ThreeWayMatcherInterface
{
    /**
     * Perform 3-way matching on a vendor bill.
     *
     * Validates bill lines against:
     * - Purchase Order (expected quantity and price)
     * - Goods Received Note (actual received quantity)
     * - Vendor-specific tolerance configuration
     *
     * @param string $billId Bill ULID
     * @return MatchingResultInterface
     * @throws \Nexus\Payable\Exceptions\BillNotFoundException
     * @throws \Nexus\Payable\Exceptions\ThreeWayMatchFailedException
     */
    public function match(string $billId): MatchingResultInterface;

    /**
     * Validate a single bill line against PO and GRN.
     *
     * @param VendorBillLineInterface $billLine Bill line to validate
     * @param array $poLine Purchase order line data
     * @param array $grnLine Goods received note line data
     * @param MatchingToleranceInterface $tolerance Vendor tolerance rules
     * @return LineMatchingResultInterface
     */
    public function matchLine(
        VendorBillLineInterface $billLine,
        array $poLine,
        array $grnLine,
        MatchingToleranceInterface $tolerance
    ): LineMatchingResultInterface;

    /**
     * Check if bill can be auto-matched.
     *
     * @param string $billId Bill ULID
     * @return bool
     */
    public function canAutoMatch(string $billId): bool;

    /**
     * Get matching status for a bill.
     *
     * @param string $billId Bill ULID
     * @return string Matching status (pending, matched, variance_review, failed)
     */
    public function getMatchingStatus(string $billId): string;

    /**
     * Override matching variance for manual approval.
     *
     * @param string $billId Bill ULID
     * @param string $userId Approver user ID
     * @param string $reason Approval reason
     * @return MatchingResultInterface
     */
    public function overrideVariance(string $billId, string $userId, string $reason): MatchingResultInterface;
}
