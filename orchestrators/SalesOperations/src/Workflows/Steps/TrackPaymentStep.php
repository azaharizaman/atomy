<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Workflows\Steps;

use Nexus\SalesOperations\Contracts\CreditManagerInterface;
use Nexus\SalesOperations\Contracts\InvoiceProviderInterface;
use Nexus\SalesOperations\Contracts\SagaStepInterface;
use Nexus\SalesOperations\DTOs\SagaStepContext;
use Nexus\SalesOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class TrackPaymentStep implements SagaStepInterface
{
    public function __construct(
        private InvoiceProviderInterface $invoiceProvider,
        private CreditManagerInterface $creditManager,
        private ?LoggerInterface $logger = null,
    ) {}

    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function getId(): string
    {
        return 'track_payment';
    }

    public function getName(): string
    {
        return 'Track Payment';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Tracking payment', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $invoiceId = $context->get('invoice_id')
                ?? $context->getStepOutput('create_invoice', 'invoice_id');

            if ($invoiceId === null) {
                return SagaStepResult::failure('Invoice ID is required');
            }

            $invoice = $this->invoiceProvider->findById($context->tenantId, $invoiceId);

            if ($invoice === null) {
                return SagaStepResult::failure("Invoice {$invoiceId} not found");
            }

            $paymentAmount = $context->get('payment_amount');
            $paymentId = $context->get('payment_id');

            if ($paymentAmount !== null && $paymentId !== null) {
                $this->invoiceProvider->applyPayment(
                    $context->tenantId,
                    $invoiceId,
                    $paymentAmount
                );

                $orderId = $context->getStepOutput('confirm_order', 'order_id');
                if ($orderId !== null) {
                    $this->creditManager->convertReservationToUsed(
                        $context->tenantId,
                        $orderId
                    );
                }

                return SagaStepResult::success([
                    'invoice_id' => $invoiceId,
                    'payment_applied' => true,
                    'payment_amount' => $paymentAmount,
                    'payment_id' => $paymentId,
                    'balance_due' => $invoice->getBalanceDue() - $paymentAmount,
                ]);
            }

            return SagaStepResult::success([
                'invoice_id' => $invoiceId,
                'payment_applied' => false,
                'balance_due' => $invoice->getBalanceDue(),
                'status' => $invoice->getStatus(),
                'awaiting_payment' => !$invoice->isPaid(),
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Payment tracking failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::nonCriticalFailure(
                'Payment tracking failed: ' . $e->getMessage()
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Reversing payment tracking', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        $paymentApplied = $context->getStepOutput('track_payment', 'payment_applied', false);

        if (!$paymentApplied) {
            return SagaStepResult::compensated([
                'message' => 'No payment to reverse',
            ]);
        }

        return SagaStepResult::compensated([
            'message' => 'Payment reversal handled by accounting system',
        ]);
    }

    public function hasCompensation(): bool
    {
        return false;
    }

    public function getOrder(): int
    {
        return 6;
    }

    public function isRequired(): bool
    {
        return false;
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
