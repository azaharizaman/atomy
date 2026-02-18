<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Workflows\Steps;

use Nexus\SalesOperations\Contracts\SagaStepInterface;
use Nexus\SalesOperations\Contracts\StockReservationInterface;
use Nexus\SalesOperations\DTOs\SagaStepContext;
use Nexus\SalesOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class ReserveStockStep implements SagaStepInterface
{
    public function __construct(
        private StockReservationInterface $stockReservation,
        private ?LoggerInterface $logger = null,
    ) {}

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function getId(): string
    {
        return 'reserve_stock';
    }

    public function getName(): string
    {
        return 'Reserve Stock for Order';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Reserving stock for order', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $orderId = $context->get('order_id');
            $orderLines = $context->get('order_lines')
                ?? $context->get('order_data.lines', []);
            $warehouseId = $context->get('warehouse_id')
                ?? $context->get('order_data.warehouse_id', 'default');

            if (empty($orderLines)) {
                return SagaStepResult::failure('Order lines are required');
            }

            $reservations = [];
            $failedItems = [];

            foreach ($orderLines as $line) {
                $productId = $line['product_variant_id'] ?? $line->productVariantId ?? null;
                $quantity = $line['quantity'] ?? $line->quantity ?? 0;

                if ($productId === null || $quantity <= 0) {
                    continue;
                }

                $success = $this->stockReservation->reserve(
                    $context->tenantId,
                    $orderId ?? $context->sagaInstanceId,
                    $productId,
                    $warehouseId,
                    $quantity
                );

                if (!$success) {
                    $failedItems[] = [
                        'product_id' => $productId,
                        'quantity' => $quantity,
                    ];
                } else {
                    $reservations[] = [
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'warehouse_id' => $warehouseId,
                    ];
                }
            }

            if (!empty($failedItems)) {
                foreach ($reservations as $reservation) {
                    $this->stockReservation->releaseLine(
                        $context->tenantId,
                        $orderId ?? $context->sagaInstanceId,
                        $reservation['product_id']
                    );
                }

                return SagaStepResult::failure(
                    sprintf(
                        'Failed to reserve stock for %d items',
                        count($failedItems)
                    )
                );
            }

            return SagaStepResult::success([
                'order_id' => $orderId,
                'reservations' => $reservations,
                'warehouse_id' => $warehouseId,
                'stock_reserved' => true,
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Stock reservation failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                'Stock reservation failed: ' . $e->getMessage()
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Releasing stock reservations', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $orderId = $context->get('order_id')
                ?? $context->getStepOutput('reserve_stock', 'order_id');

            if ($orderId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No order to release stock for',
                ]);
            }

            $this->stockReservation->release(
                $context->tenantId,
                $orderId
            );

            return SagaStepResult::compensated([
                'released_order_id' => $orderId,
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Stock release failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to release stock: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 2;
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getTimeout(): int
    {
        return 120;
    }

    public function getRetryAttempts(): int
    {
        return 3;
    }
}
