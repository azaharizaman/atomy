<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\ProcurementOperations\Contracts\PaymentProcessingCoordinatorInterface;
use Nexus\ProcurementOperations\DataProviders\PaymentDataProvider;
use Nexus\ProcurementOperations\DTOs\PaymentBatchContext;
use Nexus\ProcurementOperations\DTOs\PaymentResult;
use Nexus\ProcurementOperations\DTOs\ProcessPaymentRequest;
use Nexus\ProcurementOperations\Events\PaymentExecutedEvent;
use Nexus\ProcurementOperations\Events\PaymentScheduledEvent;
use Nexus\ProcurementOperations\Exceptions\PaymentException;
use Nexus\ProcurementOperations\Rules\Payment\PaymentRuleRegistry;
use Nexus\ProcurementOperations\Services\PaymentBatchBuilder;
use Nexus\ProcurementOperations\Services\PaymentIdGenerator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for payment processing operations.
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * - Acts as traffic cop - directs flow but doesn't do the work
 * - Delegates to DataProvider for context aggregation
 * - Delegates to Rules for validation
 * - Delegates to Service for batch building
 * - Dispatches events for side effects
 */
final readonly class PaymentProcessingCoordinator implements PaymentProcessingCoordinatorInterface
{
    public function __construct(
        private PaymentDataProvider $dataProvider,
        private PaymentRuleRegistry $rules,
        private PaymentBatchBuilder $batchBuilder,
        private PaymentIdGenerator $idGenerator,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get the logger instance, or a NullLogger if none was injected.
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    /**
     * Process a payment request.
     *
     * @throws PaymentException If validation fails or processing error
     */
    public function process(ProcessPaymentRequest $request): PaymentResult
    {
        $this->getLogger()->info('Processing payment request', [
            'tenantId' => $request->tenantId,
            'invoiceCount' => count($request->vendorBillIds),
            'paymentMethod' => $request->paymentMethod,
        ]);

        // 1. Build payment batch context
        $context = $this->batchBuilder->buildBatch($request);

        // 2. Validate using rules
        $this->rules->validate($context);

        // 3. Schedule or execute based on request
        if ($request->scheduledDate !== null) {
            return $this->schedule(
                tenantId: $request->tenantId,
                vendorBillIds: $request->vendorBillIds,
                scheduledDate: $request->scheduledDate,
                paymentMethod: $request->paymentMethod,
                bankAccountId: $request->bankAccountId,
                scheduledBy: $request->processedBy,
            );
        }

        return $this->executePayment($request, $context);
    }

    /**
     * Schedule a payment for a future date.
     *
     * @param array<string> $vendorBillIds
     * @throws PaymentException
     */
    public function schedule(
        string $tenantId,
        array $vendorBillIds,
        \DateTimeImmutable $scheduledDate,
        string $paymentMethod,
        string $bankAccountId,
        string $scheduledBy,
    ): PaymentResult {
        $this->getLogger()->info('Scheduling payment', [
            'tenantId' => $tenantId,
            'scheduledDate' => $scheduledDate->format('Y-m-d'),
            'vendorBillCount' => count($vendorBillIds),
        ]);

        // Build batch context
        $batchId = $this->idGenerator->generateBatchId();
        $context = $this->dataProvider->buildBatchContext(
            tenantId: $tenantId,
            paymentBatchId: $batchId,
            vendorBillIds: $vendorBillIds,
            paymentMethod: $paymentMethod,
            bankAccountId: $bankAccountId,
        );

        // Validate rules
        $this->rules->validate($context);

        // Generate payment reference
        $paymentReference = $this->idGenerator->generatePaymentReference();
        $paymentId = $this->idGenerator->generatePaymentId();

        // Dispatch event
        $this->eventDispatcher?->dispatch(new PaymentScheduledEvent(
            tenantId: $tenantId,
            paymentBatchId: $batchId,
            paymentId: $paymentId,
            vendorBillIds: $vendorBillIds,
            totalAmountCents: $context->netAmountCents,
            currency: $context->currency,
            scheduledDate: $scheduledDate,
            scheduledBy: $scheduledBy,
        ));

        $this->getLogger()->info('Payment scheduled successfully', [
            'paymentId' => $paymentId,
            'batchId' => $batchId,
            'scheduledDate' => $scheduledDate->format('Y-m-d'),
        ]);

        return PaymentResult::scheduled(
            paymentId: $paymentId,
            paymentReference: $paymentReference,
            scheduledDate: $scheduledDate,
            totalAmountCents: $context->netAmountCents,
            paidInvoiceIds: $vendorBillIds,
            paymentBatchId: $batchId,
            message: sprintf(
                'Payment of %s %s scheduled for %s',
                $context->currency,
                number_format($context->netAmountCents / 100, 2),
                $scheduledDate->format('Y-m-d')
            ),
        );
    }

    /**
     * Execute a scheduled payment batch.
     *
     * @throws PaymentException
     */
    public function executeBatch(string $tenantId, string $paymentBatchId, string $executedBy): PaymentResult
    {
        $this->getLogger()->info('Executing payment batch', [
            'tenantId' => $tenantId,
            'batchId' => $paymentBatchId,
        ]);

        $executedAt = new \DateTimeImmutable();
        $paymentReference = $this->idGenerator->generatePaymentReference();
        $paymentId = $this->idGenerator->generatePaymentId();
        $journalEntryId = $this->idGenerator->generateJournalEntryId();

        // Dispatch event
        $this->eventDispatcher?->dispatch(new PaymentExecutedEvent(
            tenantId: $tenantId,
            paymentBatchId: $paymentBatchId,
            paymentId: $paymentId,
            vendorBillIds: [],
            totalAmountCents: 0,
            currency: 'USD',
            executedAt: $executedAt,
            executedBy: $executedBy,
            journalEntryId: $journalEntryId,
        ));

        $this->getLogger()->info('Payment batch executed', [
            'paymentId' => $paymentId,
            'batchId' => $paymentBatchId,
            'journalEntryId' => $journalEntryId,
        ]);

        return PaymentResult::executed(
            paymentId: $paymentId,
            paymentReference: $paymentReference,
            totalAmountCents: 0,
            discountTakenCents: 0,
            netAmountCents: 0,
            paidInvoiceIds: [],
            journalEntryId: $journalEntryId,
            executedAt: $executedAt,
            message: sprintf('Payment batch %s executed successfully', $paymentBatchId),
        );
    }

    /**
     * Cancel a scheduled payment.
     *
     * @throws PaymentException
     */
    public function cancel(string $tenantId, string $paymentId, string $cancelledBy, string $reason): PaymentResult
    {
        $this->getLogger()->info('Cancelling payment', [
            'tenantId' => $tenantId,
            'paymentId' => $paymentId,
            'reason' => $reason,
        ]);

        return PaymentResult::failure(
            message: sprintf('Payment %s cancelled: %s', $paymentId, $reason),
            failureReason: $reason,
        );
    }

    /**
     * Void an executed payment (creates reversal).
     *
     * @throws PaymentException
     */
    public function void(string $tenantId, string $paymentId, string $voidedBy, string $reason): PaymentResult
    {
        $this->getLogger()->info('Voiding payment', [
            'tenantId' => $tenantId,
            'paymentId' => $paymentId,
            'reason' => $reason,
        ]);

        return PaymentResult::failure(
            message: sprintf('Payment %s voided: %s', $paymentId, $reason),
            failureReason: $reason,
            issues: ['status' => 'voided'],
        );
    }

    /**
     * Get payment status for vendor bills.
     *
     * @param array<string> $vendorBillIds
     * @return array<string, array{
     *     status: string,
     *     paymentId: ?string,
     *     scheduledDate: ?\DateTimeImmutable,
     *     executedDate: ?\DateTimeImmutable,
     *     amountCents: int
     * }>
     */
    public function getPaymentStatus(string $tenantId, array $vendorBillIds): array
    {
        $statuses = [];

        foreach ($vendorBillIds as $vendorBillId) {
            // In a real implementation, query the payment repository
            $statuses[$vendorBillId] = [
                'status' => 'pending',
                'paymentId' => null,
                'scheduledDate' => null,
                'executedDate' => null,
                'amountCents' => 0,
            ];
        }

        return $statuses;
    }

    /**
     * Execute immediate payment.
     */
    private function executePayment(ProcessPaymentRequest $request, PaymentBatchContext $context): PaymentResult
    {
        $this->getLogger()->info('Executing immediate payment', [
            'batchId' => $context->paymentBatchId,
            'netAmountCents' => $context->netAmountCents,
        ]);

        $executedAt = new \DateTimeImmutable();
        $paymentReference = $this->idGenerator->generatePaymentReference();
        $paymentId = $this->idGenerator->generatePaymentId();
        $journalEntryId = $this->idGenerator->generateJournalEntryId();

        // Dispatch event
        $this->eventDispatcher?->dispatch(new PaymentExecutedEvent(
            tenantId: $request->tenantId,
            paymentBatchId: $context->paymentBatchId,
            paymentId: $paymentId,
            vendorBillIds: $request->vendorBillIds,
            totalAmountCents: $context->netAmountCents,
            currency: $context->currency,
            executedAt: $executedAt,
            executedBy: $request->processedBy,
            journalEntryId: $journalEntryId,
        ));

        $this->getLogger()->info('Payment executed successfully', [
            'paymentId' => $paymentId,
            'batchId' => $context->paymentBatchId,
            'journalEntryId' => $journalEntryId,
        ]);

        return PaymentResult::executed(
            paymentId: $paymentId,
            paymentReference: $paymentReference,
            totalAmountCents: $context->totalAmountCents,
            discountTakenCents: $context->totalDiscountCents,
            netAmountCents: $context->netAmountCents,
            paidInvoiceIds: $request->vendorBillIds,
            journalEntryId: $journalEntryId,
            executedAt: $executedAt,
            message: sprintf(
                'Payment of %s %s executed successfully',
                $context->currency,
                number_format($context->netAmountCents / 100, 2)
            ),
        );
    }
}
