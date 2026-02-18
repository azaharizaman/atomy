<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Workflows\Steps;

use Nexus\SalesOperations\Contracts\SagaStepInterface;
use Nexus\SalesOperations\Contracts\ShipmentProviderInterface;
use Nexus\SalesOperations\Contracts\SalesOrderProviderInterface;
use Nexus\SalesOperations\DTOs\SagaStepContext;
use Nexus\SalesOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class CreateShipmentStep implements SagaStepInterface
{
    public function __construct(
        private ShipmentProviderInterface $shipmentProvider,
        private SalesOrderProviderInterface $orderProvider,
        private ?LoggerInterface $logger = null,
    ) {}

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function getId(): string
    {
        return 'create_shipment';
    }

    public function getName(): string
    {
        return 'Create Shipment';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Creating shipment', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $orderId = $context->get('order_id')
                ?? $context->getStepOutput('confirm_order', 'order_id');

            if ($orderId === null) {
                return SagaStepResult::failure('Order ID is required');
            }

            $order = $this->orderProvider->findById($context->tenantId, $orderId);

            if ($order === null) {
                return SagaStepResult::failure("Order {$orderId} not found");
            }

            $warehouseId = $context->get('warehouse_id')
                ?? $context->getStepOutput('reserve_stock', 'warehouse_id')
                ?? 'default';

            $orderLines = $order->getLines();
            $shipmentLines = [];

            foreach ($orderLines as $line) {
                $shipmentLines[] = [
                    'order_line_id' => $line->getId(),
                    'product_variant_id' => $line->getProductVariantId(),
                    'quantity' => $line->getRemainingToShip(),
                ];
            }

            $shipment = $this->shipmentProvider->create($context->tenantId, [
                'order_id' => $orderId,
                'warehouse_id' => $warehouseId,
                'lines' => $shipmentLines,
                'shipped_by' => $context->userId,
            ]);

            return SagaStepResult::success([
                'shipment_id' => $shipment->getId(),
                'shipment_number' => $shipment->getShipmentNumber(),
                'order_id' => $orderId,
                'warehouse_id' => $warehouseId,
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Shipment creation failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                'Shipment creation failed: ' . $e->getMessage()
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Cancelling shipment', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $shipmentId = $context->getStepOutput('create_shipment', 'shipment_id');

            if ($shipmentId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No shipment to cancel',
                ]);
            }

            $this->shipmentProvider->cancel(
                $context->tenantId,
                $shipmentId,
                'Saga compensation - process rolled back'
            );

            return SagaStepResult::compensated([
                'cancelled_shipment_id' => $shipmentId,
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Shipment cancellation failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to cancel shipment: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 4;
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
        return 2;
    }
}
