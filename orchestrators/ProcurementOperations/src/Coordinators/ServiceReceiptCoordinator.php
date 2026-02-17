<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\ProcurementOperations\Contracts\ServiceReceiptCoordinatorInterface;
use Nexus\ProcurementOperations\DTOs\ServiceReceiptRequest;
use Nexus\ProcurementOperations\DTOs\ServiceReceiptResult;
use Nexus\Procurement\Contracts\PurchaseOrderQueryInterface;
use Nexus\Procurement\Contracts\GoodsReceiptPersistInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates the recording and acceptance of services (Service Receipts).
 */
final readonly class ServiceReceiptCoordinator implements ServiceReceiptCoordinatorInterface
{
    public function __construct(
        private PurchaseOrderQueryInterface $poQuery,
        private GoodsReceiptPersistInterface $grPersist,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * @inheritDoc
     */
    public function record(ServiceReceiptRequest $request): ServiceReceiptResult
    {
        $this->logger->info('Recording service receipt', [
            'tenant_id' => $request->tenantId,
            'purchase_order_id' => $request->purchaseOrderId,
        ]);

        try {
            $po = $this->poQuery->findById($request->purchaseOrderId);
            if (!$po) {
                return ServiceReceiptResult::failure('Purchase order not found.');
            }

            // A service receipt is recorded as a non-inventory goods receipt
            $receiptId = $this->grPersist->create([
                'tenant_id' => $request->tenantId,
                'purchase_order_id' => $request->purchaseOrderId,
                'received_by' => $request->recordedBy,
                'type' => 'service',
                'line_items' => $request->lineItems,
                'metadata' => array_merge($request->metadata, [
                    'approval_reference' => $request->approvalReference,
                    'is_service' => true,
                ]),
            ]);

            $totalAmountCents = array_reduce(
                $request->lineItems,
                fn(int $carry, array $item) => $carry + ($item['amountCents'] ?? 0),
                0
            );

            return ServiceReceiptResult::success(
                receiptId: $receiptId,
                status: 'confirmed',
                totalAmountCents: $totalAmountCents,
                message: 'Service receipt recorded successfully.'
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to record service receipt', [
                'error' => $e->getMessage(),
            ]);
            return ServiceReceiptResult::failure($e->getMessage());
        }
    }
}
