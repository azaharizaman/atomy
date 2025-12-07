<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Listeners;

use Nexus\Common\ValueObjects\Money;
use Nexus\Procurement\Events\GoodsReceiptCreatedEvent;
use Nexus\ProcurementOperations\Contracts\AccrualServiceInterface;
use Nexus\ProcurementOperations\Exceptions\AccrualException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Posts GR-IR accrual entries when goods are received.
 *
 * This listener handles:
 * - GR-IR clearing account posting
 * - Inventory asset recognition
 * - Price variance recording (if applicable)
 *
 * GL Entry Posted:
 * - DR: Inventory Asset (at received value)
 * - CR: GR-IR Clearing Account (at received value)
 *
 * This creates a liability for goods received but not yet invoiced.
 * The clearing account will be reversed when the vendor invoice is matched.
 */
final readonly class PostAccrualOnGoodsReceipt
{
    public function __construct(
        private AccrualServiceInterface $accrualService,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Handle the goods receipt created event.
     */
    public function handle(GoodsReceiptCreatedEvent $event): void
    {
        $this->logger->info('Processing goods receipt for accrual posting', [
            'goods_receipt_id' => $event->goodsReceiptId,
            'goods_receipt_number' => $event->goodsReceiptNumber,
            'purchase_order_id' => $event->purchaseOrderId,
            'total_value_cents' => $event->totalValueCents,
            'currency' => $event->currency,
        ]);

        try {
            // Validate total value is positive
            if ($event->totalValueCents <= 0) {
                $this->logger->warning('Skipping accrual posting for zero-value goods receipt', [
                    'goods_receipt_id' => $event->goodsReceiptId,
                    'total_value_cents' => $event->totalValueCents,
                ]);
                return;
            }

            $receivedValue = Money::fromCents($event->totalValueCents, $event->currency);

            // Post the GR-IR accrual entry
            $this->accrualService->postGoodsReceiptAccrual(
                tenantId: $event->tenantId,
                goodsReceiptId: $event->goodsReceiptId,
                purchaseOrderId: $event->purchaseOrderId,
                lineItems: $event->lineItems,
                postedBy: $event->receivedBy,
            );

            $this->logger->info('Successfully posted GR-IR accrual entry', [
                'goods_receipt_id' => $event->goodsReceiptId,
                'purchase_order_id' => $event->purchaseOrderId,
                'amount_cents' => $event->totalValueCents,
                'currency' => $event->currency,
            ]);
        } catch (AccrualException $e) {
            $this->logger->error('Failed to post accrual entry', [
                'goods_receipt_id' => $event->goodsReceiptId,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);

            // Re-throw to allow the message queue to handle retry logic
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error during accrual posting', [
                'goods_receipt_id' => $event->goodsReceiptId,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);

            throw AccrualException::postingFailed(
                $event->goodsReceiptId,
                'Unexpected error: ' . $e->getMessage(),
            );
        }
    }
}
