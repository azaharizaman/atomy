<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Workflows;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Nexus\ProcurementOperations\DTOs\Financial\BankFileGenerationResult;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchCreatedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchSubmittedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchApprovedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchRejectedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentBatchProcessedEvent;
use Nexus\ProcurementOperations\Events\Payment\PaymentItemFailedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Payment Processing Workflow
 * 
 * Stateful workflow (Saga) for managing payment batch lifecycle:
 * DRAFT → PENDING_APPROVAL → [APPROVED | REJECTED] → PROCESSING → [COMPLETED | FAILED]
 * 
 * Supports:
 * - Multi-level approval based on amount thresholds
 * - Multiple payment methods (ACH, Wire, Check)
 * - Bank file generation (NACHA, ISO 20022)
 * - Early payment discount tracking
 * - Payment failure handling with retry capability
 * 
 * @see PaymentBatchData for batch state management
 * @see PaymentItemData for individual payment items
 */
final class PaymentProcessingWorkflow
{
    private const STATE_DRAFT = 'draft';
    private const STATE_PENDING_APPROVAL = 'pending_approval';
    private const STATE_APPROVED = 'approved';
    private const STATE_REJECTED = 'rejected';
    private const STATE_PROCESSING = 'processing';
    private const STATE_COMPLETED = 'completed';
    private const STATE_FAILED = 'failed';
    private const STATE_CANCELLED = 'cancelled';

    private string $currentState;
    private ?PaymentBatchData $batch = null;
    private array $approvalHistory = [];
    private array $processingResults = [];
    private array $failedItems = [];
    private ?BankFileGenerationResult $bankFileResult = null;

    public function __construct(
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
        $this->currentState = self::STATE_DRAFT;
    }

    /**
     * Get current workflow state
     */
    public function getCurrentState(): string
    {
        return $this->currentState;
    }

    /**
     * Get batch data
     */
    public function getBatch(): ?PaymentBatchData
    {
        return $this->batch;
    }

    /**
     * Start a new payment batch workflow
     */
    public function start(
        string $batchId,
        string $batchNumber,
        string $tenantId,
        string $paymentMethod,
        string $bankAccountId,
        \DateTimeImmutable $paymentDate,
        string $currency,
        string $createdBy,
    ): self {
        $this->validateState(self::STATE_DRAFT);

        $this->batch = PaymentBatchData::create(
            batchId: $batchId,
            batchNumber: $batchNumber,
            tenantId: $tenantId,
            paymentMethod: $paymentMethod,
            bankAccountId: $bankAccountId,
            paymentDate: $paymentDate,
            currency: $currency,
            createdBy: $createdBy,
        );

        $this->logger->info('Payment batch workflow started', [
            'batch_id' => $batchId,
            'batch_number' => $batchNumber,
            'payment_method' => $paymentMethod,
        ]);

        $this->dispatchEvent(PaymentBatchCreatedEvent::create(
            batchId: $batchId,
            batchNumber: $batchNumber,
            tenantId: $tenantId,
            paymentMethod: $paymentMethod,
            bankAccountId: $bankAccountId,
            paymentDate: $paymentDate,
            currency: $currency,
            createdBy: $createdBy,
        ));

        return $this;
    }

    /**
     * Add payment item to the batch
     */
    public function addPaymentItem(PaymentItemData $item): self
    {
        $this->validateState(self::STATE_DRAFT);

        if ($this->batch === null) {
            throw new \RuntimeException('Batch not initialized. Call start() first.');
        }

        $this->batch = $this->batch->withPaymentItem($item);

        $this->logger->debug('Payment item added to batch', [
            'batch_id' => $this->batch->batchId,
            'payment_item_id' => $item->paymentItemId,
            'vendor_id' => $item->vendorId,
            'amount' => $item->amount->toArray(),
        ]);

        return $this;
    }

    /**
     * Submit batch for approval
     */
    public function submitForApproval(string $submittedBy): self
    {
        $this->validateState(self::STATE_DRAFT);
        $this->requireBatch();

        if ($this->batch->itemCount === 0) {
            throw new \RuntimeException('Cannot submit empty batch for approval');
        }

        $this->batch = $this->batch->withSubmitForApproval();
        $this->currentState = self::STATE_PENDING_APPROVAL;

        $this->logger->info('Payment batch submitted for approval', [
            'batch_id' => $this->batch->batchId,
            'total_amount' => $this->batch->totalAmount->toArray(),
            'item_count' => $this->batch->itemCount,
            'submitted_by' => $submittedBy,
        ]);

        $this->dispatchEvent(PaymentBatchSubmittedEvent::create(
            batchId: $this->batch->batchId,
            batchNumber: $this->batch->batchNumber,
            tenantId: $this->batch->tenantId,
            totalAmount: $this->batch->totalAmount,
            itemCount: $this->batch->itemCount,
            vendorCount: count($this->batch->getVendorIds()),
            submittedBy: $submittedBy,
        ));

        return $this;
    }

    /**
     * Approve the batch
     * 
     * Supports multi-level approval. If thresholds are configured,
     * multiple approvals may be required before final approval.
     */
    public function approve(
        string $approvedBy,
        ?string $comments = null,
        bool $isFinalApproval = true,
    ): self {
        $this->validateState(self::STATE_PENDING_APPROVAL);
        $this->requireBatch();

        $approvalLevel = count($this->approvalHistory) + 1;

        $this->approvalHistory[] = [
            'approved_by' => $approvedBy,
            'approved_at' => (new \DateTimeImmutable())->format('c'),
            'level' => $approvalLevel,
            'comments' => $comments,
            'is_final' => $isFinalApproval,
        ];

        if ($isFinalApproval) {
            $this->batch = $this->batch->withApproval($approvedBy);
            $this->currentState = self::STATE_APPROVED;

            $this->logger->info('Payment batch approved (final)', [
                'batch_id' => $this->batch->batchId,
                'approved_by' => $approvedBy,
                'approval_level' => $approvalLevel,
            ]);

            $this->dispatchEvent(PaymentBatchApprovedEvent::finalApproval(
                batchId: $this->batch->batchId,
                batchNumber: $this->batch->batchNumber,
                tenantId: $this->batch->tenantId,
                totalAmount: $this->batch->totalAmount,
                itemCount: $this->batch->itemCount,
                approvedBy: $approvedBy,
                approvalLevel: $approvalLevel,
                comments: $comments,
            ));
        } else {
            $this->logger->info('Payment batch approved (intermediate)', [
                'batch_id' => $this->batch->batchId,
                'approved_by' => $approvedBy,
                'approval_level' => $approvalLevel,
            ]);

            $this->dispatchEvent(PaymentBatchApprovedEvent::intermediateApproval(
                batchId: $this->batch->batchId,
                batchNumber: $this->batch->batchNumber,
                tenantId: $this->batch->tenantId,
                totalAmount: $this->batch->totalAmount,
                itemCount: $this->batch->itemCount,
                approvedBy: $approvedBy,
                approvalLevel: $approvalLevel,
                comments: $comments,
            ));
        }

        return $this;
    }

    /**
     * Reject the batch
     */
    public function reject(string $rejectedBy, string $reason): self
    {
        $this->validateState(self::STATE_PENDING_APPROVAL);
        $this->requireBatch();

        $this->currentState = self::STATE_REJECTED;
        $rejectionLevel = count($this->approvalHistory) + 1;

        $this->approvalHistory[] = [
            'rejected_by' => $rejectedBy,
            'rejected_at' => (new \DateTimeImmutable())->format('c'),
            'level' => $rejectionLevel,
            'reason' => $reason,
            'action' => 'reject',
        ];

        $this->logger->warning('Payment batch rejected', [
            'batch_id' => $this->batch->batchId,
            'rejected_by' => $rejectedBy,
            'reason' => $reason,
        ]);

        $this->dispatchEvent(PaymentBatchRejectedEvent::create(
            batchId: $this->batch->batchId,
            batchNumber: $this->batch->batchNumber,
            tenantId: $this->batch->tenantId,
            totalAmount: $this->batch->totalAmount,
            itemCount: $this->batch->itemCount,
            rejectedBy: $rejectedBy,
            reason: $reason,
            rejectionLevel: $rejectionLevel,
        ));

        return $this;
    }

    /**
     * Process the batch - generate bank file
     * 
     * This is where the actual bank file is generated based on payment method.
     */
    public function process(
        callable $bankFileGenerator,
    ): self {
        $this->validateState(self::STATE_APPROVED);
        $this->requireBatch();

        $this->currentState = self::STATE_PROCESSING;

        $this->logger->info('Processing payment batch', [
            'batch_id' => $this->batch->batchId,
            'payment_method' => $this->batch->paymentMethod,
            'item_count' => $this->batch->itemCount,
        ]);

        try {
            // Generate bank file using provided generator
            /** @var BankFileGenerationResult $result */
            $result = $bankFileGenerator($this->batch);

            $this->bankFileResult = $result;

            if (!$result->success) {
                $this->currentState = self::STATE_FAILED;
                $this->failedItems = $result->failedPayments;

                $this->logger->error('Payment batch processing failed', [
                    'batch_id' => $this->batch->batchId,
                    'error' => $result->errorMessage,
                ]);

                return $this;
            }

            // Update batch with bank file reference
            $this->batch = $this->batch->withProcessing(
                bankFileReference: $result->checksum,
                bankFileName: $result->fileName,
            );

            $successfulItems = $result->getSuccessCount();
            $failedItems = $result->getFailureCount();
            $this->failedItems = $result->failedPayments;

            $this->logger->info('Payment batch bank file generated', [
                'batch_id' => $this->batch->batchId,
                'file_name' => $result->fileName,
                'successful_items' => $successfulItems,
                'failed_items' => $failedItems,
            ]);

            $this->dispatchEvent(PaymentBatchProcessedEvent::partialSuccess(
                batchId: $this->batch->batchId,
                batchNumber: $this->batch->batchNumber,
                tenantId: $this->batch->tenantId,
                paymentMethod: $this->batch->paymentMethod,
                totalAmount: $this->batch->totalAmount,
                totalItems: $this->batch->itemCount,
                successfulItems: $successfulItems,
                failedItems: $failedItems,
                bankFileReference: $result->checksum,
                bankFileName: $result->fileName,
                fileFormat: $result->fileFormat,
            ));

        } catch (\Throwable $e) {
            $this->currentState = self::STATE_FAILED;

            $this->logger->error('Payment batch processing error', [
                'batch_id' => $this->batch->batchId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $this;
    }

    /**
     * Complete the batch after successful bank transmission
     */
    public function complete(): self
    {
        $this->validateState(self::STATE_PROCESSING);
        $this->requireBatch();

        $this->batch = $this->batch->withCompletion();
        $this->currentState = self::STATE_COMPLETED;

        $this->logger->info('Payment batch completed', [
            'batch_id' => $this->batch->batchId,
        ]);

        return $this;
    }

    /**
     * Record individual payment item failure
     */
    public function recordItemFailure(
        string $paymentItemId,
        string $vendorId,
        string $vendorName,
        Money $amount,
        string $failureCode,
        string $failureReason,
        bool $isRetryable = false,
        ?string $bankErrorCode = null,
    ): self {
        $this->requireBatch();

        $this->failedItems[$paymentItemId] = [
            'reason' => $failureReason,
            'code' => $failureCode,
            'is_retryable' => $isRetryable,
        ];

        $event = match ($failureCode) {
            'INVALID_BANK_ACCOUNT' => PaymentItemFailedEvent::invalidBankAccount(
                paymentItemId: $paymentItemId,
                batchId: $this->batch->batchId,
                vendorId: $vendorId,
                vendorName: $vendorName,
                amount: $amount,
                bankErrorCode: $bankErrorCode,
            ),
            'INSUFFICIENT_FUNDS' => PaymentItemFailedEvent::insufficientFunds(
                paymentItemId: $paymentItemId,
                batchId: $this->batch->batchId,
                vendorId: $vendorId,
                vendorName: $vendorName,
                amount: $amount,
            ),
            default => PaymentItemFailedEvent::systemError(
                paymentItemId: $paymentItemId,
                batchId: $this->batch->batchId,
                vendorId: $vendorId,
                vendorName: $vendorName,
                amount: $amount,
                errorMessage: $failureReason,
            ),
        };

        $this->dispatchEvent($event);

        return $this;
    }

    /**
     * Cancel the batch
     */
    public function cancel(string $cancelledBy, string $reason): self
    {
        $allowedStates = [
            self::STATE_DRAFT,
            self::STATE_PENDING_APPROVAL,
            self::STATE_REJECTED,
        ];

        if (!in_array($this->currentState, $allowedStates, true)) {
            throw new \RuntimeException(
                "Cannot cancel batch in state '{$this->currentState}'. " .
                "Allowed states: " . implode(', ', $allowedStates)
            );
        }

        $this->requireBatch();
        $this->currentState = self::STATE_CANCELLED;

        $this->logger->info('Payment batch cancelled', [
            'batch_id' => $this->batch->batchId,
            'cancelled_by' => $cancelledBy,
            'reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Get approval history
     */
    public function getApprovalHistory(): array
    {
        return $this->approvalHistory;
    }

    /**
     * Get failed items
     */
    public function getFailedItems(): array
    {
        return $this->failedItems;
    }

    /**
     * Get bank file result
     */
    public function getBankFileResult(): ?BankFileGenerationResult
    {
        return $this->bankFileResult;
    }

    /**
     * Check if workflow is in terminal state
     */
    public function isTerminal(): bool
    {
        return in_array($this->currentState, [
            self::STATE_COMPLETED,
            self::STATE_FAILED,
            self::STATE_CANCELLED,
        ], true);
    }

    /**
     * Check if workflow can be retried
     */
    public function canRetry(): bool
    {
        return $this->currentState === self::STATE_FAILED &&
            !empty(array_filter($this->failedItems, fn($item) => $item['is_retryable'] ?? false));
    }

    /**
     * Export workflow state for persistence
     */
    public function exportState(): array
    {
        return [
            'current_state' => $this->currentState,
            'batch' => $this->batch?->toArray(),
            'approval_history' => $this->approvalHistory,
            'processing_results' => $this->processingResults,
            'failed_items' => $this->failedItems,
            'bank_file_result' => $this->bankFileResult?->toArray(),
        ];
    }

    /**
     * Restore workflow from persisted state
     */
    public static function restoreState(
        array $state,
        ?EventDispatcherInterface $eventDispatcher = null,
        LoggerInterface $logger = new NullLogger(),
    ): self {
        $workflow = new self($eventDispatcher, $logger);
        $workflow->currentState = $state['current_state'] ?? self::STATE_DRAFT;
        $workflow->approvalHistory = $state['approval_history'] ?? [];
        $workflow->processingResults = $state['processing_results'] ?? [];
        $workflow->failedItems = $state['failed_items'] ?? [];

        // Note: Batch and BankFileResult would need to be reconstructed
        // from the stored array data in a real implementation

        return $workflow;
    }

    /**
     * Get available transitions from current state
     */
    public function getAvailableTransitions(): array
    {
        return match ($this->currentState) {
            self::STATE_DRAFT => ['add_item', 'submit_for_approval', 'cancel'],
            self::STATE_PENDING_APPROVAL => ['approve', 'reject', 'cancel'],
            self::STATE_APPROVED => ['process'],
            self::STATE_PROCESSING => ['complete', 'record_failure'],
            self::STATE_REJECTED => ['cancel'],
            self::STATE_FAILED => ['retry', 'cancel'],
            default => [],
        };
    }

    /**
     * Validate current state matches expected
     */
    private function validateState(string $expectedState): void
    {
        if ($this->currentState !== $expectedState) {
            throw new \RuntimeException(
                "Invalid workflow state. Expected '{$expectedState}', got '{$this->currentState}'"
            );
        }
    }

    /**
     * Ensure batch is initialized
     */
    private function requireBatch(): void
    {
        if ($this->batch === null) {
            throw new \RuntimeException('Batch not initialized. Call start() first.');
        }
    }

    /**
     * Dispatch event if dispatcher available
     */
    private function dispatchEvent(object $event): void
    {
        $this->eventDispatcher?->dispatch($event);
    }
}
