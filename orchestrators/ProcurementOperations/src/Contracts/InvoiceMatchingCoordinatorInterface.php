<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\MatchInvoiceRequest;
use Nexus\ProcurementOperations\DTOs\MatchingResult;

/**
 * Contract for invoice matching (3-way match) coordination.
 *
 * Handles the matching of vendor invoices against purchase orders
 * and goods receipts with configurable variance tolerances.
 */
interface InvoiceMatchingCoordinatorInterface
{
    /**
     * Perform three-way match: PO ↔ GR ↔ Invoice.
     *
     * This operation:
     * 1. Fetches PO, GR(s), and Invoice data
     * 2. Compares quantities and prices
     * 3. Calculates variances
     * 4. Checks against configured tolerances
     * 5. If matched: reverses GR-IR accrual, posts AP liability
     * 6. Dispatches InvoiceMatchedEvent or InvoiceMatchFailedEvent
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\MatchingException
     */
    public function match(MatchInvoiceRequest $request): MatchingResult;

    /**
     * Perform two-way match: PO ↔ Invoice (no GR required).
     *
     * Used for service purchases or when goods receipt is not applicable.
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\MatchingException
     */
    public function matchTwoWay(
        string $tenantId,
        string $vendorBillId,
        string $purchaseOrderId,
        string $performedBy
    ): MatchingResult;

    /**
     * Force match with variance approval.
     *
     * Used when variance exceeds tolerance but is approved by authorized user.
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\MatchingException
     * @throws \Nexus\ProcurementOperations\Exceptions\UnauthorizedApprovalException
     */
    public function forceMatch(
        string $tenantId,
        string $vendorBillId,
        string $purchaseOrderId,
        array $goodsReceiptIds,
        string $approvedBy,
        string $approvalReason
    ): MatchingResult;

    /**
     * Get match status for a vendor bill.
     *
     * @return array{
     *     status: string,
     *     matchedAt: ?\DateTimeImmutable,
     *     variances: array<string, float>,
     *     withinTolerance: bool
     * }
     */
    public function getMatchStatus(string $tenantId, string $vendorBillId): array;
}
