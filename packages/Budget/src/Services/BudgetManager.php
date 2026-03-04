<?php

declare(strict_types=1);

namespace Nexus\Budget\Services;

use Nexus\Budget\Contracts\BudgetInterface;
use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Budget\Contracts\BudgetApprovalWorkflowInterface;
use Nexus\Budget\Contracts\BudgetTransactionPersistInterface;
use Nexus\Budget\Contracts\BudgetTransactionQueryInterface;
use Nexus\Budget\Contracts\CurrencyConverterInterface;
use Nexus\Budget\Contracts\PeriodGatewayInterface;
use Nexus\Budget\Contracts\SettingsGatewayInterface;
use Nexus\Budget\Contracts\AuditLoggerInterface;
use Nexus\Budget\Enums\BudgetStatus;
use Nexus\Budget\Enums\BudgetType;
use Nexus\Budget\Enums\TransactionType;
use Nexus\Budget\Events\BudgetCreatedEvent;
use Nexus\Budget\Events\BudgetApprovedEvent;
use Nexus\Budget\Events\BudgetCommittedEvent;
use Nexus\Budget\Events\BudgetActualRecordedEvent;
use Nexus\Budget\Events\BudgetExceededEvent;
use Nexus\Budget\Events\BudgetLockedEvent;
use Nexus\Budget\Events\BudgetTransferredEvent;
use Nexus\Budget\Events\BudgetAmendedEvent;
use Nexus\Budget\Exceptions\BudgetExceededException;
use Nexus\Budget\Exceptions\InvalidBudgetStatusException;
use Nexus\Budget\Exceptions\PeriodClosedException;
use Nexus\Budget\Exceptions\CurrencyMismatchException;
use Nexus\Budget\Exceptions\InsufficientBudgetForTransferException;
use Nexus\Budget\ValueObjects\BudgetAllocation;
use Nexus\Budget\ValueObjects\BudgetVariance;
use Nexus\Budget\ValueObjects\BudgetAvailabilityResult;
use Nexus\Common\ValueObjects\Money;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Budget Manager - Main orchestrator for budget operations
 */
final readonly class BudgetManager implements BudgetManagerInterface
{
    public function __construct(
        private BudgetRepositoryInterface $budgetRepository,
        private BudgetTransactionPersistInterface $transactionPersist,
        private BudgetTransactionQueryInterface $transactionQuery,
        private BudgetApprovalWorkflowInterface $workflowService,
        private PeriodGatewayInterface $periodManager,
        private CurrencyConverterInterface $currencyConverter,
        private SettingsGatewayInterface $settings,
        private AuditLoggerInterface $auditLogger,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {}

    public function createBudget(array $data): BudgetInterface
    {
        $allocation = BudgetAllocation::fromArray($data);
        
        // Validate period
        $period = $this->periodManager->findById($allocation->periodId);
        if (!$period) {
            throw new \InvalidArgumentException("Period not found: {$allocation->periodId}");
        }

        if ($period->isClosed()) {
            throw new PeriodClosedException($allocation->periodId);
        }

        // Create budget entity
        $budget = $this->budgetRepository->create($allocation);

        // Publish event
        $this->eventDispatcher->dispatch(new BudgetCreatedEvent(
            budgetId: $budget->getId(),
            periodId: $budget->getPeriodId(),
            budgetType: $budget->getType(),
            allocatedAmount: $budget->getAllocatedAmount(),
            currency: $budget->getCurrency(),
            departmentId: $budget->getDepartmentId(),
            projectId: $budget->getProjectId()
        ));

        $this->auditLogger->log(
            $budget->getId(),
            'budget_created',
            "Budget {$budget->getName()} created with allocation {$budget->getAllocatedAmount()}"
        );

        $this->logger->info('Budget created', [
            'budget_id' => $budget->getId(),
            'allocated_amount' => (string) $budget->getAllocatedAmount(),
        ]);

        return $budget;
    }

    public function allocate(string $budgetId, Money $amount): void
    {
        $budget = $this->budgetRepository->findById($budgetId);
        if (!$budget) {
            throw new \InvalidArgumentException("Budget not found: {$budgetId}");
        }

        if (!$budget->getStatus()->canModify()) {
            throw new InvalidBudgetStatusException(
                $budgetId,
                $budget->getStatus(),
                "Cannot modify budget in {$budget->getStatus()->value} status"
            );
        }

        // Update allocation - wrap money in BudgetAllocation if needed by repo
        $allocation = new BudgetAllocation(
            periodId: $budget->getPeriodId(),
            allocatedAmount: $amount,
            justification: 'Manual allocation'
        );
        
        $this->budgetRepository->updateAllocation($budgetId, $allocation);

        $this->eventDispatcher->dispatch(new BudgetAmendedEvent(
            budgetId: $budgetId,
            periodId: $budget->getPeriodId(),
            previousAmount: $budget->getAllocatedAmount(),
            newAmount: $amount,
            amendedBy: 'system', // Would come from auth context
            reason: 'Budget allocation'
        ));

        $this->auditLogger->log(
            $budgetId,
            'budget_allocated',
            "Budget allocation updated to {$amount}"
        );
    }

    public function commitAmount(
        string $budgetId,
        Money $amount,
        string $accountId,
        string $sourceType,
        string $sourceId,
        int $sourceLineNumber,
        ?string $costCenterId = null,
        ?string $workflowApprovalId = null
    ): void {
        $budget = $this->budgetRepository->findById($budgetId);
        if (!$budget) {
            throw new \InvalidArgumentException("Budget not found: {$budgetId}");
        }

        if (!$budget->getStatus()->canCommit()) {
            throw new InvalidBudgetStatusException(
                $budgetId,
                $budget->getStatus(),
                "Cannot commit against budget in {$budget->getStatus()->value} status"
            );
        }

        // Currency validation
        if ($amount->getCurrency() !== $budget->getCurrency()) {
            throw new CurrencyMismatchException($amount->getCurrency(), $budget->getCurrency());
        }

        // Check availability
        $available = $budget->getAvailableAmount();
        if ($amount->getAmount() > $available->getAmount()) {
            $this->handleBudgetExceedance($budget, $amount, $sourceId);
            return; // Workflow will handle completion
        }

        // Record commitment
        $this->transactionPersist->recordCommitment(
            budgetId: $budgetId,
            amount: $amount,
            accountId: $accountId,
            sourceType: $sourceType,
            sourceId: $sourceId,
            sourceLineNumber: $sourceLineNumber,
            costCenterId: $costCenterId,
            workflowApprovalId: $workflowApprovalId,
            transactionType: TransactionType::Commitment
        );

        // Calculate utilization
        $newCommitted = $budget->getCommittedAmount()->add($amount);
        $utilizationPct = ($newCommitted->getAmount() / $budget->getAllocatedAmount()->getAmount()) * 100;

        $this->eventDispatcher->dispatch(new BudgetCommittedEvent(
            budgetId: $budgetId,
            periodId: $budget->getPeriodId(),
            committedAmount: $amount,
            currentUtilizationPercentage: $utilizationPct,
            sourceDocumentId: $sourceId,
            transactionType: TransactionType::Commitment
        ));

        $this->auditLogger->log(
            $budgetId,
            'budget_committed',
            "Committed {$amount} against budget (Document: {$sourceId}, Type: {$sourceType}, Line: {$sourceLineNumber})"
        );
    }

    public function releaseCommitment(
        string $budgetId,
        Money $amount,
        string $sourceType,
        string $sourceId,
        int $sourceLineNumber
    ): void {
        $transactions = $this->transactionQuery->findBySource(
            sourceType: $sourceType,
            sourceId: $sourceId
        );
        
        foreach ($transactions as $transaction) {
            if ($transaction->getType() === TransactionType::Commitment &&
                $transaction->getBudgetId() === $budgetId &&
                $transaction->getAmount()->equals($amount)
            ) {
                $this->transactionPersist->releaseCommitment($transaction->getId());
                
                $this->auditLogger->log(
                    $transaction->getBudgetId(),
                    'commitment_released',
                    "Released commitment {$amount} (Document: {$sourceId}, Type: {$sourceType}, Line: {$sourceLineNumber})"
                );
            }
        }
    }

    public function recordActual(
        string $budgetId,
        Money $amount,
        string $accountId,
        string $sourceType,
        string $sourceId,
        int $sourceLineNumber
    ): void {
        $budget = $this->budgetRepository->findById($budgetId);
        if (!$budget) {
            throw new \InvalidArgumentException("Budget not found: {$budgetId}");
        }

        // Currency validation
        if ($amount->getCurrency() !== $budget->getCurrency()) {
            throw new CurrencyMismatchException($amount->getCurrency(), $budget->getCurrency());
        }

        // Record actual transaction
        $this->transactionPersist->recordActual(
            $budgetId,
            $amount,
            $sourceId,
            TransactionType::Actual
        );

        // Release commitment automatically
        $this->releaseCommitment($budgetId, $amount, $sourceType, $sourceId, $sourceLineNumber);

        $this->eventDispatcher->dispatch(new BudgetActualRecordedEvent(
            budgetId: $budgetId,
            periodId: $budget->getPeriodId(),
            actualAmount: $amount,
            sourceDocumentId: $sourceId,
            transactionType: TransactionType::Actual,
            commitmentReleased: true
        ));

        $this->auditLogger->log(
            $budgetId,
            'actual_recorded',
            "Recorded actual {$amount} (Document: {$sourceId})"
        );
    }

    public function calculateVariance(string $budgetId): BudgetVariance
    {
        $budget = $this->budgetRepository->findById($budgetId);
        if (!$budget) {
            throw new \InvalidArgumentException("Budget not found: {$budgetId}");
        }

        return new BudgetVariance(
            allocated: $budget->getAllocatedAmount(),
            committed: $budget->getCommittedAmount(),
            actual: $budget->getActualAmount(),
            available: $budget->getAvailableAmount(),
            variance: $budget->getVariance(),
            variancePercentage: $budget->getVariancePercentage(),
            isRevenueBudget: $budget->isRevenueBudget()
        );
    }

    public function checkAvailability(string $budgetId, Money $requestedAmount): BudgetAvailabilityResult
    {
        $budget = $this->budgetRepository->findById($budgetId);
        if (!$budget) {
            throw new \InvalidArgumentException("Budget not found: {$budgetId}");
        }

        $available = $budget->getAvailableAmount();
        $isAvailable = $requestedAmount->getAmount() <= $available->getAmount();
        
        return new BudgetAvailabilityResult(
            isAvailable: $isAvailable,
            requestedAmount: $requestedAmount,
            availableAmount: $available,
            reason: $isAvailable ? 'Budget available' : 'Insufficient budget availability'
        );
    }

    public function lockBudget(string $budgetId): void
    {
        $budget = $this->budgetRepository->findById($budgetId);
        if (!$budget) {
            throw new \InvalidArgumentException("Budget not found: {$budgetId}");
        }

        if (!$budget->getStatus()->canLock()) {
            throw new InvalidBudgetStatusException(
                $budgetId,
                $budget->getStatus(),
                "Cannot lock budget in {$budget->getStatus()->value} status"
            );
        }

        $this->budgetRepository->updateStatus($budgetId, BudgetStatus::Locked);

        $this->eventDispatcher->dispatch(new BudgetLockedEvent(
            budgetId: $budgetId,
            periodId: $budget->getPeriodId(),
            reason: 'Budget locked',
            lockedBy: 'system' // Would come from auth context
        ));

        $this->auditLogger->log($budgetId, 'budget_locked', "Budget locked");
    }

    public function transferAllocation(
        string $fromBudgetId,
        string $toBudgetId,
        Money $amount,
        string $justification
    ): void {
        $fromBudget = $this->budgetRepository->findById($fromBudgetId);
        $toBudget = $this->budgetRepository->findById($toBudgetId);

        if (!$fromBudget || !$toBudget) {
            throw new \InvalidArgumentException('Source or destination budget not found');
        }

        // Validation
        if ($amount->getAmount() > $fromBudget->getAvailableAmount()->getAmount()) {
            throw new InsufficientBudgetForTransferException(
                $fromBudgetId,
                $toBudgetId,
                $amount,
                $fromBudget->getAvailableAmount()
            );
        }

        if ($fromBudget->getCurrency() !== $toBudget->getCurrency()) {
            throw new CurrencyMismatchException($fromBudget->getCurrency(), $toBudget->getCurrency());
        }

        // Perform transfer
        $this->budgetRepository->transferAllocation($fromBudgetId, $toBudgetId, $amount);

        $this->eventDispatcher->dispatch(new BudgetTransferredEvent(
            fromBudgetId: $fromBudgetId,
            toBudgetId: $toBudgetId,
            transferredAmount: $amount,
            justification: $justification,
            periodId: $fromBudget->getPeriodId()
        ));

        $this->auditLogger->log(
            $fromBudgetId,
            'budget_transfer_out',
            "Transferred {$amount} to budget {$toBudgetId}: {$justification}"
        );

        $this->auditLogger->log(
            $toBudgetId,
            'budget_transfer_in',
            "Received {$amount} from budget {$fromBudgetId}: {$justification}"
        );
    }

    public function amendBudget(string $budgetId, Money $newAllocation, string $reason): void
    {
        $budget = $this->budgetRepository->findById($budgetId);
        if (!$budget) {
            throw new \InvalidArgumentException("Budget not found: {$budgetId}");
        }

        if (!$budget->getStatus()->canModify()) {
            throw new InvalidBudgetStatusException(
                $budgetId,
                $budget->getStatus(),
                "Cannot amend budget in {$budget->getStatus()->value} status"
            );
        }

        $previousAmount = $budget->getAllocatedAmount();
        
        $this->budgetRepository->amendAllocation($budgetId, $newAllocation);

        $this->eventDispatcher->dispatch(new BudgetAmendedEvent(
            budgetId: $budgetId,
            periodId: $budget->getPeriodId(),
            previousAmount: $previousAmount,
            newAmount: $newAllocation,
            amendedBy: 'system', // Would come from auth context
            reason: $reason
        ));

        $this->auditLogger->log(
            $budgetId,
            'budget_amended',
            "Budget amended from {$previousAmount} to {$newAllocation}: {$reason}"
        );
    }

    public function createSimulation(string $baseBudgetId, array $modifications): BudgetInterface
    {
        $baseBudget = $this->budgetRepository->findById($baseBudgetId);
        if (!$baseBudget) {
            throw new \InvalidArgumentException("Base budget not found: {$baseBudgetId}");
        }

        return $this->budgetRepository->createSimulation($baseBudgetId, $modifications);
    }

    /**
     * Handle budget exceedance through workflow
     */
    private function handleBudgetExceedance(
        BudgetInterface $budget,
        Money $requestedAmount,
        string $sourceDocumentId
    ): void {
        $exceedanceAmount = $requestedAmount->subtract($budget->getAvailableAmount());
        
        $exception = new BudgetExceededException(
            $budget->getId(),
            $budget->getAvailableAmount(),
            $requestedAmount,
            $budget->getType()
        );

        // Publish event for notifications
        $this->eventDispatcher->dispatch(new BudgetExceededEvent(
            budgetId: $budget->getId(),
            periodId: $budget->getPeriodId(),
            availableAmount: $budget->getAvailableAmount(),
            requestedAmount: $requestedAmount,
            exceedanceAmount: $exceedanceAmount,
            sourceDocumentId: $sourceDocumentId,
            context: [
                'budget_name' => $budget->getName(),
                'department_id' => $budget->getDepartmentId(),
            ]
        ));

        // Route to workflow if required
        if ($exception->requiresWorkflowApproval()) {
            $this->workflowService->requestBudgetOverrideApproval(
                $budget->getId(),
                $requestedAmount,
                $sourceDocumentId,
                $exception->getApprovalLevel()
            );
        }

        throw $exception;
    }
}
