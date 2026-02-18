<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Workflows\Steps;

use Nexus\SalesOperations\Contracts\SagaStepInterface;
use Nexus\SalesOperations\Contracts\SalesOrderProviderInterface;
use Nexus\SalesOperations\DTOs\SagaStepContext;
use Nexus\SalesOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class ConfirmOrderStep implements SagaStepInterface
{
    public function __construct(
        private SalesOrderProviderInterface $orderProvider,
        private ?LoggerInterface $logger = null,
    ) {}

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function getId(): string
    {
        return 'confirm_order';
    }

    public function getName(): string
    {
        return 'Confirm Sales Order';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Confirming sales order', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $orderId = $context->get('order_id');
            $orderData = $context->get('order_data');

            if ($orderId !== null) {
                $order = $this->orderProvider->confirm(
                    $context->tenantId,
                    $orderId,
                    $context->userId
                );

                return SagaStepResult::success([
                    'order_id' => $order->getId(),
                    'order_number' => $order->getOrderNumber(),
                    'status' => $order->getStatus(),
                    'confirmed' => true,
                ]);
            }

            if ($orderData !== null) {
                $order = $this->orderProvider->create($context->tenantId, [
                    'customer_id' => $orderData['customer_id'],
                    'lines' => $orderData['lines'] ?? [],
                    'payment_terms' => $orderData['payment_terms'] ?? 'NET_30',
                    'shipping_address' => $orderData['shipping_address'] ?? null,
                    'billing_address' => $orderData['billing_address'] ?? null,
                    'created_by' => $context->userId,
                    'status' => 'confirmed',
                ]);

                return SagaStepResult::success([
                    'order_id' => $order->getId(),
                    'order_number' => $order->getOrderNumber(),
                    'status' => $order->getStatus(),
                    'confirmed' => true,
                ]);
            }

            return SagaStepResult::failure(
                'Either order_id or order_data is required'
            );
        } catch (\Throwable $e) {
            $this->getLogger()->error('Order confirmation failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                'Order confirmation failed: ' . $e->getMessage()
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Cancelling confirmed order', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $orderId = $context->getStepOutput('confirm_order', 'order_id');

            if ($orderId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No order to cancel',
                ]);
            }

            $this->orderProvider->cancel(
                $context->tenantId,
                $orderId,
                'Saga compensation - process rolled back'
            );

            return SagaStepResult::compensated([
                'cancelled_order_id' => $orderId,
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Order cancellation failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to cancel order: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 3;
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getTimeout(): int
    {
        return 60;
    }

    public function getRetryAttempts(): int
    {
        return 2;
    }
}
