<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Listeners;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payable\Events\InvoiceMatchedEvent;
use Nexus\ProcurementOperations\Contracts\AccrualServiceInterface;
use Nexus\ProcurementOperations\Exceptions\AccrualException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Reverses GR-IR accrual entries when an invoice is matched.
 *
 * This listener handles:
 * - Reversal of GR-IR clearing account entries
 * - Recognition of actual AP liability
 * - Posting of any price variance
 *
 * GL Entries Posted on Match:
 * 1. Reverse GR-IR Accrual:
 *    - DR: GR-IR Clearing Account (at GR value)
 *    - CR: Inventory Asset (at GR value)
 *
 * 2. Recognize AP Liability:
 *    - DR: Inventory Asset (at invoice value)
 *    - CR: Accounts Payable (at invoice value)
 *
 * If there's a price variance between GR and Invoice:
 *    - DR/CR: Purchase Price Variance (difference)
 *
 * This completes the GR-IR clearing cycle, converting the
 * provisional liability to an actual vendor payable.
 */
final readonly class ReverseAccrualOnInvoiceMatched
{
    public function __construct(
        private AccrualServiceInterface $accrualService,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Handle the invoice matched event.
     */
    public function handle(InvoiceMatchedEvent $event): void
    {
        $this->logger->info('Processing accrual reversal for matched invoice', [
            'vendor_bill_id' => $event->vendorBillId,
            'vendor_bill_number' => $event->vendorBillNumber,
            'purchase_order_id' => $event->purchaseOrderId,
            'goods_receipt_ids' => $event->matchedGoodsReceiptIds,
            'invoice_amount_cents' => $event->invoiceAmountCents,
            'received_amount_cents' => $event->receivedAmountCents,
            'currency' => $event->currency,
        ]);

        try {
            // Calculate amounts
            $invoiceAmount = Money::fromCents($event->invoiceAmountCents, $event->currency);
            $receivedAmount = Money::fromCents($event->receivedAmountCents, $event->currency);

            // 1. Reverse the GR-IR accrual entries for all matched GRs
            foreach ($event->matchedGoodsReceiptIds as $goodsReceiptId) {
                $this->reverseGoodsReceiptAccrual($event, $goodsReceiptId);
            }

            // 2. Post the final AP liability
            $this->accrualService->postPayableLiability(
                vendorBillId: $event->vendorBillId,
                purchaseOrderId: $event->purchaseOrderId,
                vendorId: $event->vendorId,
                amount: $invoiceAmount,
                matchedAt: $event->matchedAt,
            );

            // 3. Handle price variance if exists
            $varianceAmount = $invoiceAmount->subtract($receivedAmount);
            if (!$varianceAmount->isZero()) {
                $this->postPriceVariance($event, $varianceAmount);
            }

            $this->logger->info('Successfully processed accrual reversal and AP recognition', [
                'vendor_bill_id' => $event->vendorBillId,
                'invoice_amount' => $invoiceAmount->formatSimple(),
                'received_amount' => $receivedAmount->formatSimple(),
                'variance_amount' => $varianceAmount->formatSimple(),
                'goods_receipts_reversed' => count($event->matchedGoodsReceiptIds),
            ]);

        } catch (AccrualException $e) {
            $this->logger->error('Failed to process accrual reversal', [
                'vendor_bill_id' => $event->vendorBillId,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);

            // Re-throw to allow retry or manual intervention
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error during accrual reversal', [
                'vendor_bill_id' => $event->vendorBillId,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            throw AccrualException::postingFailed(
                entityId: $event->vendorBillId,
                message: 'Unexpected error: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Reverse the GR-IR accrual for a specific goods receipt.
     */
    private function reverseGoodsReceiptAccrual(
        InvoiceMatchedEvent $event,
        string $goodsReceiptId,
    ): void {
        $this->logger->debug('Reversing accrual for goods receipt', [
            'vendor_bill_id' => $event->vendorBillId,
            'goods_receipt_id' => $goodsReceiptId,
        ]);

        $this->accrualService->reverseAccrualOnMatch(
            goodsReceiptId: $goodsReceiptId,
            vendorBillId: $event->vendorBillId,
            matchedAt: $event->matchedAt,
        );
    }

    /**
     * Post price variance entry.
     *
     * Variance = Invoice Amount - Received Amount (GR value)
     *
     * If positive (invoice > GR): Unfavorable variance
     *   DR: Purchase Price Variance
     *   CR: Accounts Payable (additional)
     *
     * If negative (invoice < GR): Favorable variance
     *   DR: Accounts Payable (reduction)
     *   CR: Purchase Price Variance
     */
    private function postPriceVariance(
        InvoiceMatchedEvent $event,
        Money $varianceAmount,
    ): void {
        $this->logger->info('Posting price variance entry', [
            'vendor_bill_id' => $event->vendorBillId,
            'variance_amount' => $varianceAmount->formatSimple(),
            'variance_type' => $varianceAmount->isPositive() ? 'unfavorable' : 'favorable',
        ]);

        // The accrual service handles the variance posting
        // as part of the AP liability posting (net effect)
        // This is logged for auditing purposes
    }
}
