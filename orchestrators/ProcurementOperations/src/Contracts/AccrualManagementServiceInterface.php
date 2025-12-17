<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\GrIrAccrualData;

/**
 * Accrual Management Service Interface
 * 
 * Defines the contract for GR/IR (Goods Receipt / Invoice Receipt) accrual management:
 * - Create accruals when goods are received before invoice
 * - Match accruals when invoices are received
 * - Age and write-off unmatched accruals
 * - Generate accrual reports
 * 
 * GR/IR accruals ensure proper expense recognition according to GAAP/IFRS
 * matching principles.
 * 
 * @see GrIrAccrualData
 */
interface AccrualManagementServiceInterface
{
    /**
     * Create GR/IR accrual when goods are received
     *
     * @param string $tenantId Tenant identifier
     * @param string $purchaseOrderId PO that was received against
     * @param string $purchaseOrderNumber PO number for reference
     * @param string $goodsReceiptId Goods receipt document ID
     * @param string $goodsReceiptNumber GR document number
     * @param \DateTimeImmutable $receiptDate Date goods were received
     * @param string $vendorId Vendor identifier
     * @param string $vendorName Vendor display name
     * @param Money $accrualAmount Amount to accrue
     * @param int $lineCount Number of PO lines received
     * @param string $createdBy User who created the GR
     * @return GrIrAccrualData Created accrual record
     */
    public function createAccrual(
        string $tenantId,
        string $purchaseOrderId,
        string $purchaseOrderNumber,
        string $goodsReceiptId,
        string $goodsReceiptNumber,
        \DateTimeImmutable $receiptDate,
        string $vendorId,
        string $vendorName,
        Money $accrualAmount,
        int $lineCount,
        string $createdBy,
    ): GrIrAccrualData;

    /**
     * Match accrual with received invoice
     *
     * @param string $accrualId Accrual to match
     * @param string $invoiceId Invoice document ID
     * @param string $invoiceNumber Invoice number
     * @param Money $invoiceAmount Invoice amount
     * @param \DateTimeImmutable $invoiceDate Invoice date
     * @param string $matchedBy User who matched
     * @return GrIrAccrualData Updated accrual record
     */
    public function matchWithInvoice(
        string $accrualId,
        string $invoiceId,
        string $invoiceNumber,
        Money $invoiceAmount,
        \DateTimeImmutable $invoiceDate,
        string $matchedBy,
    ): GrIrAccrualData;

    /**
     * Partially match accrual with invoice
     * 
     * Used when invoice amount differs from accrual amount
     *
     * @param string $accrualId Accrual to match
     * @param string $invoiceId Invoice document ID
     * @param string $invoiceNumber Invoice number
     * @param Money $matchedAmount Amount being matched
     * @param Money $varianceAmount Difference (positive = invoice higher)
     * @param string $varianceReason Reason for variance
     * @param string $matchedBy User who matched
     * @return GrIrAccrualData Updated accrual record
     */
    public function partialMatchWithInvoice(
        string $accrualId,
        string $invoiceId,
        string $invoiceNumber,
        Money $matchedAmount,
        Money $varianceAmount,
        string $varianceReason,
        string $matchedBy,
    ): GrIrAccrualData;

    /**
     * Write off aged accrual
     *
     * @param string $accrualId Accrual to write off
     * @param string $writeOffReason Reason for write-off
     * @param string $writeOffBy User authorizing write-off
     * @param string|null $writeOffAccountId GL account for write-off
     * @return GrIrAccrualData Updated accrual record
     */
    public function writeOffAccrual(
        string $accrualId,
        string $writeOffReason,
        string $writeOffBy,
        ?string $writeOffAccountId = null,
    ): GrIrAccrualData;

    /**
     * Get unmatched accruals
     *
     * @param string $tenantId Tenant identifier
     * @param \DateTimeImmutable|null $asOfDate Date for aging calculation
     * @return array<GrIrAccrualData> Unmatched accruals
     */
    public function getUnmatchedAccruals(
        string $tenantId,
        ?\DateTimeImmutable $asOfDate = null,
    ): array;

    /**
     * Get aged accruals requiring attention
     *
     * @param string $tenantId Tenant identifier
     * @param int $agingThresholdDays Days after which accrual is considered aged
     * @param \DateTimeImmutable|null $asOfDate Date for aging calculation
     * @return array<GrIrAccrualData> Aged accruals
     */
    public function getAgedAccruals(
        string $tenantId,
        int $agingThresholdDays = 30,
        ?\DateTimeImmutable $asOfDate = null,
    ): array;

    /**
     * Get accruals by vendor
     *
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param bool $unmatchedOnly Only return unmatched accruals
     * @return array<GrIrAccrualData> Vendor accruals
     */
    public function getAccrualsByVendor(
        string $tenantId,
        string $vendorId,
        bool $unmatchedOnly = true,
    ): array;

    /**
     * Get accruals by purchase order
     *
     * @param string $tenantId Tenant identifier
     * @param string $purchaseOrderId PO identifier
     * @return array<GrIrAccrualData> PO accruals
     */
    public function getAccrualsByPurchaseOrder(
        string $tenantId,
        string $purchaseOrderId,
    ): array;

    /**
     * Get total accrual balance
     *
     * @param string $tenantId Tenant identifier
     * @param \DateTimeImmutable|null $asOfDate Balance as of date
     * @return Money Total unmatched accrual balance
     */
    public function getTotalAccrualBalance(
        string $tenantId,
        ?\DateTimeImmutable $asOfDate = null,
    ): Money;

    /**
     * Generate accrual aging report
     *
     * @param string $tenantId Tenant identifier
     * @param \DateTimeImmutable $asOfDate Report date
     * @param array<int> $agingBuckets Days for aging buckets (e.g., [30, 60, 90])
     * @return array{
     *     as_of_date: string,
     *     total_accrual_balance: Money,
     *     aging_buckets: array<array{
     *         bucket_label: string,
     *         min_days: int,
     *         max_days: int|null,
     *         count: int,
     *         amount: Money
     *     }>,
     *     by_vendor: array<array{
     *         vendor_id: string,
     *         vendor_name: string,
     *         accrual_count: int,
     *         total_amount: Money,
     *         oldest_accrual_days: int
     *     }>
     * }
     */
    public function generateAgingReport(
        string $tenantId,
        \DateTimeImmutable $asOfDate,
        array $agingBuckets = [30, 60, 90],
    ): array;

    /**
     * Suggest matching invoices for accrual
     *
     * @param string $accrualId Accrual to find matches for
     * @param float $tolerancePercent Acceptable variance percentage
     * @return array<array{
     *     invoice_id: string,
     *     invoice_number: string,
     *     invoice_date: \DateTimeImmutable,
     *     invoice_amount: Money,
     *     variance_amount: Money,
     *     variance_percent: float,
     *     match_confidence: float
     * }> Suggested invoice matches
     */
    public function suggestMatchingInvoices(
        string $accrualId,
        float $tolerancePercent = 5.0,
    ): array;

    /**
     * Auto-match accruals with invoices
     * 
     * Automatically matches accruals with invoices that have
     * exact or near-exact matches within tolerance.
     *
     * @param string $tenantId Tenant identifier
     * @param float $tolerancePercent Acceptable variance percentage
     * @param string $matchedBy System user for auto-match
     * @return array{
     *     matched_count: int,
     *     total_matched_amount: Money,
     *     matches: array<array{
     *         accrual_id: string,
     *         invoice_id: string,
     *         matched_amount: Money,
     *         variance_amount: Money
     *     }>
     * }
     */
    public function autoMatchAccruals(
        string $tenantId,
        float $tolerancePercent = 0.01,
        string $matchedBy = 'SYSTEM',
    ): array;

    /**
     * Reverse accrual (e.g., for returned goods)
     *
     * @param string $accrualId Accrual to reverse
     * @param string $reversalReason Reason for reversal
     * @param string $reversedBy User authorizing reversal
     * @return GrIrAccrualData Reversal accrual record
     */
    public function reverseAccrual(
        string $accrualId,
        string $reversalReason,
        string $reversedBy,
    ): GrIrAccrualData;

    /**
     * Get accrual by ID
     *
     * @param string $accrualId Accrual identifier
     * @return GrIrAccrualData|null Accrual data or null if not found
     */
    public function getAccrual(string $accrualId): ?GrIrAccrualData;

    /**
     * Calculate period accrual entries for GL posting
     *
     * @param string $tenantId Tenant identifier
     * @param \DateTimeImmutable $periodEndDate Accounting period end date
     * @return array{
     *     period_end_date: string,
     *     accrual_entries: array<array{
     *         accrual_id: string,
     *         debit_account_id: string,
     *         credit_account_id: string,
     *         amount: Money,
     *         description: string
     *     }>,
     *     total_debit: Money,
     *     total_credit: Money
     * }
     */
    public function calculatePeriodAccrualEntries(
        string $tenantId,
        \DateTimeImmutable $periodEndDate,
    ): array;
}
