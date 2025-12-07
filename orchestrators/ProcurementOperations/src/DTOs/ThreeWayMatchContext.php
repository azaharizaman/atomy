<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Context DTO for three-way matching operations.
 *
 * Aggregates data from PO, GR, and Invoice for matching comparison.
 */
final readonly class ThreeWayMatchContext
{
    /**
     * @param string $tenantId Tenant context
     * @param string $vendorBillId Vendor bill ID
     * @param string $purchaseOrderId PO ID
     * @param array<string> $goodsReceiptIds GR IDs
     * @param array{
     *     billId: string,
     *     billNumber: string,
     *     vendorId: string,
     *     vendorName: string,
     *     totalAmountCents: int,
     *     taxAmountCents: int,
     *     currency: string,
     *     invoiceDate: \DateTimeImmutable,
     *     dueDate: \DateTimeImmutable,
     *     status: string
     * } $invoiceInfo Invoice summary
     * @param array{
     *     poId: string,
     *     poNumber: string,
     *     vendorId: string,
     *     totalAmountCents: int,
     *     currency: string,
     *     status: string
     * } $purchaseOrderInfo PO summary
     * @param array<int, array{
     *     lineId: string,
     *     productId: string,
     *     description: string,
     *     poQuantity: float,
     *     poUnitPriceCents: int,
     *     grQuantity: float,
     *     invoiceQuantity: float,
     *     invoiceUnitPriceCents: int,
     *     uom: string
     * }> $lineComparison Line-by-line comparison data
     * @param array{
     *     totalPoAmountCents: int,
     *     totalGrValueCents: int,
     *     totalInvoiceAmountCents: int,
     *     totalPoQuantity: float,
     *     totalGrQuantity: float,
     *     totalInvoiceQuantity: float
     * } $totals Totals for comparison
     */
    public function __construct(
        public string $tenantId,
        public string $vendorBillId,
        public string $purchaseOrderId,
        public array $goodsReceiptIds,
        public array $invoiceInfo,
        public array $purchaseOrderInfo,
        public array $lineComparison,
        public array $totals,
    ) {}

    /**
     * Calculate price variance percentage.
     */
    public function calculatePriceVariancePercent(): float
    {
        if ($this->totals['totalPoAmountCents'] === 0) {
            return 0.0;
        }

        $variance = abs($this->totals['totalInvoiceAmountCents'] - $this->totals['totalPoAmountCents']);
        return ($variance / $this->totals['totalPoAmountCents']) * 100;
    }

    /**
     * Calculate quantity variance percentage (Invoice vs GR).
     */
    public function calculateQuantityVariancePercent(): float
    {
        if ($this->totals['totalGrQuantity'] === 0.0) {
            return 0.0;
        }

        $variance = abs($this->totals['totalInvoiceQuantity'] - $this->totals['totalGrQuantity']);
        return ($variance / $this->totals['totalGrQuantity']) * 100;
    }

    /**
     * Check if vendors match.
     */
    public function vendorsMatch(): bool
    {
        return $this->invoiceInfo['vendorId'] === $this->purchaseOrderInfo['vendorId'];
    }

    /**
     * Check if currencies match.
     */
    public function currenciesMatch(): bool
    {
        return $this->invoiceInfo['currency'] === $this->purchaseOrderInfo['currency'];
    }
}
