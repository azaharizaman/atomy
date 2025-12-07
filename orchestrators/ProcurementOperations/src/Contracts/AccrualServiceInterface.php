<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

/**
 * Contract for GR-IR accrual calculation and posting service.
 *
 * Handles the accrual accounting for goods received but not yet invoiced.
 * GR-IR = Goods Receipt - Invoice Receipt clearing account.
 */
interface AccrualServiceInterface
{
    /**
     * Calculate and post GR-IR accrual for goods receipt.
     *
     * Journal entry:
     * DR Inventory Asset (at PO price)
     * CR GR-IR Clearing Account
     *
     * @param string $tenantId
     * @param string $goodsReceiptId
     * @param string $purchaseOrderId
     * @param array<int, array{
     *     productId: string,
     *     quantity: float,
     *     unitPriceCents: int,
     *     totalCents: int
     * }> $lineItems
     * @param string $postedBy
     *
     * @return string Journal entry ID
     * @throws \Nexus\ProcurementOperations\Exceptions\AccrualException
     */
    public function postGoodsReceiptAccrual(
        string $tenantId,
        string $goodsReceiptId,
        string $purchaseOrderId,
        array $lineItems,
        string $postedBy
    ): string;

    /**
     * Reverse GR-IR accrual when invoice is matched.
     *
     * Journal entry:
     * DR GR-IR Clearing Account
     * CR Inventory Asset (or COGS variance if different)
     *
     * @param string $tenantId
     * @param string $vendorBillId
     * @param array<string> $goodsReceiptIds
     * @param string $postedBy
     *
     * @return string Journal entry ID
     * @throws \Nexus\ProcurementOperations\Exceptions\AccrualException
     */
    public function reverseAccrualOnMatch(
        string $tenantId,
        string $vendorBillId,
        array $goodsReceiptIds,
        string $postedBy
    ): string;

    /**
     * Post AP liability journal entry when invoice is matched.
     *
     * Journal entry:
     * DR Expense/Inventory Asset
     * CR Accounts Payable
     *
     * @param string $tenantId
     * @param string $vendorBillId
     * @param string $vendorId
     * @param int $amountCents
     * @param string $currency
     * @param string $postedBy
     *
     * @return string Journal entry ID
     * @throws \Nexus\ProcurementOperations\Exceptions\AccrualException
     */
    public function postPayableLiability(
        string $tenantId,
        string $vendorBillId,
        string $vendorId,
        int $amountCents,
        string $currency,
        string $postedBy
    ): string;

    /**
     * Post payment journal entry.
     *
     * Journal entry:
     * DR Accounts Payable
     * CR Bank Account
     *
     * @param string $tenantId
     * @param string $paymentId
     * @param string $vendorId
     * @param int $amountCents
     * @param int $discountCents Early payment discount taken
     * @param string $currency
     * @param string $bankAccountId
     * @param string $postedBy
     *
     * @return string Journal entry ID
     * @throws \Nexus\ProcurementOperations\Exceptions\AccrualException
     */
    public function postPaymentEntry(
        string $tenantId,
        string $paymentId,
        string $vendorId,
        int $amountCents,
        int $discountCents,
        string $currency,
        string $bankAccountId,
        string $postedBy
    ): string;
}
