<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Workflows\Steps;

use Nexus\SalesOperations\Contracts\InvoiceProviderInterface;
use Nexus\SalesOperations\Contracts\SagaStepInterface;
use Nexus\SalesOperations\Contracts\SalesOrderProviderInterface;
use Nexus\SalesOperations\DTOs\SagaStepContext;
use Nexus\SalesOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class CreateInvoiceStep implements SagaStepInterface
{
    public function __construct(
        private InvoiceProviderInterface $invoiceProvider,
        private SalesOrderProviderInterface $orderProvider,
        private ?LoggerInterface $logger = null,
    ) {}

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function getId(): string
    {
        return 'create_invoice';
    }

    public function getName(): string
    {
        return 'Create Invoice';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Creating invoice', [
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

            $existingInvoice = $this->invoiceProvider->findByOrder(
                $context->tenantId,
                $orderId
            );

            if ($existingInvoice !== null) {
                return SagaStepResult::success([
                    'invoice_id' => $existingInvoice->getId(),
                    'invoice_number' => $existingInvoice->getInvoiceNumber(),
                    'order_id' => $orderId,
                    'total' => $existingInvoice->getTotal(),
                    'existing' => true,
                ]);
            }

            $invoice = $this->invoiceProvider->create($context->tenantId, [
                'order_id' => $orderId,
                'customer_id' => $order->getCustomerId(),
                'lines' => $order->getLines(),
            ]);

            return SagaStepResult::success([
                'invoice_id' => $invoice->getId(),
                'invoice_number' => $invoice->getInvoiceNumber(),
                'order_id' => $orderId,
                'total' => $invoice->getTotal(),
                'balance_due' => $invoice->getBalanceDue(),
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Invoice creation failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                'Invoice creation failed: ' . $e->getMessage()
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Voiding invoice', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $invoiceId = $context->getStepOutput('create_invoice', 'invoice_id');
            $existing = $context->getStepOutput('create_invoice', 'existing', false);

            if ($invoiceId === null || $existing) {
                return SagaStepResult::compensated([
                    'message' => 'No invoice to void',
                ]);
            }

            $this->invoiceProvider->void(
                $context->tenantId,
                $invoiceId,
                'Saga compensation - process rolled back'
            );

            return SagaStepResult::compensated([
                'voided_invoice_id' => $invoiceId,
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Invoice void failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to void invoice: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 5;
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
