<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Workflows\Steps;

use Nexus\ProcurementOperations\Contracts\SagaStepInterface;
use Nexus\ProcurementOperations\DTOs\SagaStepContext;
use Nexus\ProcurementOperations\DTOs\SagaStepResult;
use Nexus\Payable\Contracts\PaymentManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Process vendor payment.
 *
 * Forward action: Creates and processes payment to vendor.
 * Compensation: Payment cannot be automatically reversed (requires manual intervention).
 */
final readonly class ProcessPaymentStep implements SagaStepInterface
{
    public function __construct(
        private PaymentManagerInterface $paymentManager,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get the logger instance, or a NullLogger if none was injected.
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function getId(): string
    {
        return 'process_payment';
    }

    public function getName(): string
    {
        return 'Process Payment';
    }

    public function getDescription(): string
    {
        return 'Processes payment to vendor for matched invoice';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Processing vendor payment', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $invoiceId = $context->get('vendor_invoice_id');
            $matchData = $context->getStepOutput('three_way_match');
            $paymentData = $context->get('payment_data', []);

            // Verify match was successful before payment
            if ($matchData === null || !in_array($matchData['match_status'] ?? '', ['matched', 'approved_with_variance'], true)) {
                return SagaStepResult::failure(
                    errorMessage: '3-way match not completed or approved, cannot process payment',
                    canRetry: false,
                );
            }

            $payment = $this->paymentManager->createPayment(
                tenantId: $context->tenantId,
                vendorInvoiceId: $invoiceId,
                amount: $matchData['matched_amount'] ?? $paymentData['amount'],
                paymentMethod: $paymentData['payment_method'] ?? 'bank_transfer',
                paymentDate: $paymentData['payment_date'] ?? date('Y-m-d'),
                createdBy: $context->userId,
                metadata: [
                    'saga_instance_id' => $context->sagaInstanceId,
                    'match_id' => $matchData['match_id'] ?? null,
                    'bank_account_id' => $paymentData['bank_account_id'] ?? null,
                    'reference' => $paymentData['reference'] ?? null,
                ],
            );

            // Submit payment for processing
            $processResult = $this->paymentManager->processPayment($payment->getId());

            if (!$processResult->isSuccessful()) {
                return SagaStepResult::failure(
                    errorMessage: 'Payment processing failed: ' . $processResult->getErrorMessage(),
                    canRetry: $processResult->canRetry(),
                );
            }

            return SagaStepResult::success([
                'payment_id' => $payment->getId(),
                'payment_reference' => $payment->getReference(),
                'payment_amount' => $payment->getAmount(),
                'payment_status' => $processResult->getStatus(),
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to process payment', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Failed to process payment: ' . $e->getMessage(),
                canRetry: false,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->warning('Payment compensation requested - requires manual intervention', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        // Payments cannot be automatically reversed
        // Log for manual review and return success to allow saga to complete compensation
        $paymentId = $context->getStepOutput('process_payment', 'payment_id');

        if ($paymentId !== null) {
            // Mark payment for manual review
            try {
                $this->paymentManager->flagForReview(
                    paymentId: $paymentId,
                    reason: 'Saga compensation - requires manual reversal',
                    flaggedBy: 'system',
                );
            } catch (\Throwable $e) {
                $this->getLogger()->error('Failed to flag payment for review', [
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return SagaStepResult::compensated([
            'payment_id' => $paymentId,
            'action_required' => 'manual_reversal',
            'message' => 'Payment flagged for manual review and reversal',
        ]);
    }

    public function hasCompensation(): bool
    {
        // Returns false because payment compensation requires manual intervention
        // The compensate method above is for flagging, not automatic reversal
        return false;
    }

    public function getOrder(): int
    {
        return 9;
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getTimeout(): int
    {
        return 3600; // 1 hour for payment processing
    }

    public function getRetryAttempts(): int
    {
        return 3;
    }
}
