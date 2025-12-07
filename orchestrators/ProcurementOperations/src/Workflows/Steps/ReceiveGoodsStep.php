<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Workflows\Steps;

use Nexus\ProcurementOperations\Contracts\SagaStepInterface;
use Nexus\ProcurementOperations\DTOs\SagaStepContext;
use Nexus\ProcurementOperations\DTOs\SagaStepResult;
use Nexus\Inventory\Contracts\GoodsReceiptManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Record goods receipt against purchase order.
 *
 * Forward action: Records goods received.
 * Compensation: Reverses the goods receipt.
 */
final readonly class ReceiveGoodsStep implements SagaStepInterface
{
    public function __construct(
        private GoodsReceiptManagerInterface $grManager,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getId(): string
    {
        return 'receive_goods';
    }

    public function getName(): string
    {
        return 'Receive Goods';
    }

    public function getDescription(): string
    {
        return 'Records receipt of goods against purchase order';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->logger->info('Recording goods receipt', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $poId = $context->getStepOutput('approve_purchase_order', 'purchase_order_id')
                ?? $context->getStepOutput('create_purchase_order', 'purchase_order_id')
                ?? $context->get('purchase_order_id');

            $grData = $context->get('goods_receipt_data', []);

            $goodsReceipt = $this->grManager->create(
                tenantId: $context->tenantId,
                purchaseOrderId: $poId,
                receivedBy: $context->userId,
                items: $grData['items'] ?? [],
                metadata: [
                    'saga_instance_id' => $context->sagaInstanceId,
                    'warehouse_id' => $grData['warehouse_id'] ?? null,
                    'delivery_note_number' => $grData['delivery_note_number'] ?? null,
                    'received_date' => $grData['received_date'] ?? date('Y-m-d'),
                ],
            );

            return SagaStepResult::success([
                'goods_receipt_id' => $goodsReceipt->getId(),
                'goods_receipt_number' => $goodsReceipt->getNumber(),
                'received_items_count' => count($grData['items'] ?? []),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to record goods receipt', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Failed to record goods receipt: ' . $e->getMessage(),
                canRetry: false,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->logger->info('Compensating: Reversing goods receipt', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $grId = $context->getStepOutput('receive_goods', 'goods_receipt_id');

            if ($grId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No goods receipt to reverse',
                ]);
            }

            $this->grManager->reverse(
                goodsReceiptId: $grId,
                reason: 'Saga compensation - process rolled back',
                reversedBy: $context->userId,
            );

            return SagaStepResult::compensated([
                'reversed_gr_id' => $grId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to reverse goods receipt during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to reverse goods receipt: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 6;
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getTimeout(): int
    {
        return 300; // 5 minutes
    }

    public function getRetryAttempts(): int
    {
        return 3;
    }
}
