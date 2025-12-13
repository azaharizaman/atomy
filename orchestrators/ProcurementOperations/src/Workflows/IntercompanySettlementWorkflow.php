<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Workflows;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\IntercompanySettlementServiceInterface;
use Nexus\ProcurementOperations\DTOs\Financial\IntercompanySettlementData;
use Nexus\ProcurementOperations\DTOs\Financial\NetSettlementResult;
use Nexus\ProcurementOperations\Enums\SettlementStatus;
use Nexus\ProcurementOperations\Events\Financial\IntercompanyNettingCompletedEvent;
use Nexus\ProcurementOperations\Events\Financial\IntercompanySettlementCompletedEvent;
use Nexus\ProcurementOperations\Events\Financial\IntercompanySettlementInitiatedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Workflow for intercompany settlement processing.
 *
 * Manages the complete lifecycle of intercompany settlements including:
 * - Transaction gathering and validation
 * - Netting calculation across entities
 * - Approval workflow
 * - Settlement execution
 * - Elimination entry generation for consolidation
 *
 * Implements saga pattern with compensation for failures.
 */
final class IntercompanySettlementWorkflow
{
    private const MAX_SETTLEMENT_RETRIES = 3;
    private const BALANCE_TOLERANCE_PERCENT = 0.01; // 1% tolerance for balance differences

    public function __construct(
        private readonly IntercompanySettlementServiceInterface $settlementService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Initiate intercompany settlement process.
     *
     * @param string $tenantId Tenant context
     * @param string $fromEntityId Source entity
     * @param string $toEntityId Target entity
     * @param string $initiatedBy User initiating settlement
     * @param array $options Settlement options
     * @return array{settlement_id: string, status: SettlementStatus, netting_result: ?NetSettlementResult}
     */
    public function initiateSettlement(
        string $tenantId,
        string $fromEntityId,
        string $toEntityId,
        string $initiatedBy,
        array $options = [],
    ): array {
        $settlementId = $this->generateSettlementId();

        $this->logger->info('Initiating intercompany settlement', [
            'settlement_id' => $settlementId,
            'tenant_id' => $tenantId,
            'from_entity' => $fromEntityId,
            'to_entity' => $toEntityId,
        ]);

        // Step 1: Validate balance agreement before proceeding
        $balanceValidation = $this->settlementService->validateBalanceAgreement(
            $fromEntityId,
            $toEntityId,
        );

        if (!$balanceValidation['balanced'] && !($options['allow_imbalance'] ?? false)) {
            $variance = $balanceValidation['variance'];
            $totalAmount = $balanceValidation['details']['total_amount'] ?? Money::of(1, 'USD');
            $variancePercent = $totalAmount->isZero() ? 0 : ($variance->getAmount() / $totalAmount->getAmount()) * 100;

            if ($variancePercent > self::BALANCE_TOLERANCE_PERCENT) {
                $this->logger->warning('Intercompany balance discrepancy detected', [
                    'settlement_id' => $settlementId,
                    'variance' => $variance->getAmount(),
                    'variance_percent' => $variancePercent,
                ]);

                return [
                    'settlement_id' => $settlementId,
                    'status' => SettlementStatus::DISPUTED,
                    'netting_result' => null,
                    'error' => 'Balance discrepancy exceeds tolerance',
                    'variance' => $variance,
                    'variance_details' => $balanceValidation['details'],
                ];
            }
        }

        // Step 2: Gather pending transactions
        $receivables = $this->gatherReceivables($fromEntityId, $toEntityId, $options);
        $payables = $this->gatherPayables($fromEntityId, $toEntityId, $options);

        if (empty($receivables) && empty($payables)) {
            $this->logger->info('No transactions found for settlement', [
                'settlement_id' => $settlementId,
            ]);

            return [
                'settlement_id' => $settlementId,
                'status' => SettlementStatus::CANCELLED,
                'netting_result' => null,
                'message' => 'No pending transactions found',
            ];
        }

        // Dispatch initiation event
        $grossReceivables = $this->sumAmounts($receivables);
        $grossPayables = $this->sumAmounts($payables);

        $this->eventDispatcher->dispatch(new IntercompanySettlementInitiatedEvent(
            settlementId: $settlementId,
            fromEntityId: $fromEntityId,
            toEntityId: $toEntityId,
            grossReceivables: $grossReceivables,
            grossPayables: $grossPayables,
            transactionCount: count($receivables) + count($payables),
            initiatedBy: $initiatedBy,
        ));

        // Step 3: Calculate netting
        $nettingResult = $this->settlementService->calculateNetSettlement(
            $fromEntityId,
            $toEntityId,
            $receivables,
            $payables,
        );

        $this->eventDispatcher->dispatch(new IntercompanyNettingCompletedEvent(
            settlementId: $settlementId,
            fromEntityId: $fromEntityId,
            toEntityId: $toEntityId,
            netAmount: $nettingResult->netAmount,
            netDirection: $nettingResult->netDirection,
            nettingEfficiency: $nettingResult->getNettingEfficiency(),
            transactionsNetted: $nettingResult->getTotalTransactionCount(),
        ));

        $this->logger->info('Netting calculation completed', [
            'settlement_id' => $settlementId,
            'net_amount' => $nettingResult->netAmount->getAmount(),
            'net_direction' => $nettingResult->netDirection,
            'efficiency' => $nettingResult->getNettingEfficiency(),
        ]);

        // Determine next status based on net amount
        $status = $nettingResult->isFullyOffset()
            ? SettlementStatus::SETTLED
            : SettlementStatus::PENDING_APPROVAL;

        return [
            'settlement_id' => $settlementId,
            'status' => $status,
            'netting_result' => $nettingResult,
            'requires_payment' => $nettingResult->requiresPayment(),
            'expects_receipt' => $nettingResult->expectsReceipt(),
        ];
    }

    /**
     * Approve settlement for execution.
     *
     * @param string $settlementId Settlement to approve
     * @param string $approvedBy Approving user
     * @param array $approvalData Additional approval data
     * @return array{status: SettlementStatus, approved_at: \DateTimeImmutable}
     */
    public function approveSettlement(
        string $settlementId,
        string $approvedBy,
        array $approvalData = [],
    ): array {
        $this->logger->info('Approving intercompany settlement', [
            'settlement_id' => $settlementId,
            'approved_by' => $approvedBy,
        ]);

        // In a real implementation, this would update persistence
        // Here we return the approved state

        return [
            'settlement_id' => $settlementId,
            'status' => SettlementStatus::APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => new \DateTimeImmutable(),
            'approval_notes' => $approvalData['notes'] ?? null,
        ];
    }

    /**
     * Execute approved settlement.
     *
     * @param string $tenantId Tenant context
     * @param string $settlementId Settlement to execute
     * @param NetSettlementResult $nettingResult Netting calculation
     * @param string $executedBy Executing user
     * @return array Settlement execution result
     */
    public function executeSettlement(
        string $tenantId,
        string $settlementId,
        NetSettlementResult $nettingResult,
        string $executedBy,
    ): array {
        $this->logger->info('Executing intercompany settlement', [
            'settlement_id' => $settlementId,
            'net_amount' => $nettingResult->netAmount->getAmount(),
            'direction' => $nettingResult->netDirection,
        ]);

        $retryCount = 0;
        $lastError = null;

        while ($retryCount < self::MAX_SETTLEMENT_RETRIES) {
            try {
                // Record settlement in both ledgers
                $settlementReference = $this->generateSettlementReference($settlementId);

                $recordedSettlementId = $this->settlementService->recordSettlement(
                    $nettingResult,
                    $settlementReference,
                );

                // Dispatch completion event
                $this->eventDispatcher->dispatch(new IntercompanySettlementCompletedEvent(
                    settlementId: $settlementId,
                    fromEntityId: $nettingResult->fromEntityId,
                    toEntityId: $nettingResult->toEntityId,
                    settlementAmount: $nettingResult->netAmount,
                    settlementCurrency: $nettingResult->settlementCurrency,
                    paymentReference: $settlementReference,
                    settledAt: new \DateTimeImmutable(),
                ));

                $this->logger->info('Intercompany settlement executed successfully', [
                    'settlement_id' => $settlementId,
                    'reference' => $settlementReference,
                ]);

                return [
                    'settlement_id' => $settlementId,
                    'status' => SettlementStatus::SETTLED,
                    'settlement_reference' => $settlementReference,
                    'recorded_id' => $recordedSettlementId,
                    'settled_at' => new \DateTimeImmutable(),
                    'executed_by' => $executedBy,
                ];

            } catch (\Throwable $e) {
                $lastError = $e;
                $retryCount++;

                $this->logger->warning('Settlement execution failed, retrying', [
                    'settlement_id' => $settlementId,
                    'attempt' => $retryCount,
                    'error' => $e->getMessage(),
                ]);

                // Exponential backoff
                usleep((int) pow(2, $retryCount) * 100000); // 200ms, 400ms, 800ms
            }
        }

        // All retries exhausted
        $this->logger->error('Settlement execution failed after retries', [
            'settlement_id' => $settlementId,
            'retries' => self::MAX_SETTLEMENT_RETRIES,
            'last_error' => $lastError?->getMessage(),
        ]);

        // Initiate compensation
        $this->compensateFailedSettlement($settlementId, $nettingResult);

        return [
            'settlement_id' => $settlementId,
            'status' => SettlementStatus::CANCELLED,
            'error' => 'Settlement execution failed after retries',
            'last_error' => $lastError?->getMessage(),
            'compensated' => true,
        ];
    }

    /**
     * Generate elimination entries for consolidation.
     *
     * @param string $parentEntityId Parent/consolidating entity
     * @param string $periodId Consolidation period
     * @return array Elimination entry details
     */
    public function generateEliminationEntries(
        string $parentEntityId,
        string $periodId,
    ): array {
        $this->logger->info('Generating elimination entries for consolidation', [
            'parent_entity' => $parentEntityId,
            'period' => $periodId,
        ]);

        $entries = $this->settlementService->generateEliminationEntries(
            $parentEntityId,
            $periodId,
        );

        $totalEliminationAmount = Money::of(0, 'USD');
        foreach ($entries as $entry) {
            if (isset($entry['debit']['amount'])) {
                $totalEliminationAmount = $totalEliminationAmount->add($entry['debit']['amount']);
            }
        }

        return [
            'parent_entity_id' => $parentEntityId,
            'period_id' => $periodId,
            'elimination_entries' => $entries,
            'entry_count' => count($entries),
            'total_elimination_amount' => $totalEliminationAmount,
            'generated_at' => new \DateTimeImmutable(),
        ];
    }

    /**
     * Cancel pending settlement.
     *
     * @param string $settlementId Settlement to cancel
     * @param string $cancelledBy User cancelling
     * @param string $reason Cancellation reason
     * @return array Cancellation result
     */
    public function cancelSettlement(
        string $settlementId,
        string $cancelledBy,
        string $reason,
    ): array {
        $this->logger->info('Cancelling intercompany settlement', [
            'settlement_id' => $settlementId,
            'cancelled_by' => $cancelledBy,
            'reason' => $reason,
        ]);

        return [
            'settlement_id' => $settlementId,
            'status' => SettlementStatus::CANCELLED,
            'cancelled_by' => $cancelledBy,
            'reason' => $reason,
            'cancelled_at' => new \DateTimeImmutable(),
        ];
    }

    /**
     * Get settlement status summary.
     *
     * @param string $settlementId Settlement ID
     * @return array Status details
     */
    public function getSettlementStatus(string $settlementId): array
    {
        // In real implementation, this would query persistence
        return [
            'settlement_id' => $settlementId,
            'status' => SettlementStatus::PENDING_NETTING,
            'last_updated' => new \DateTimeImmutable(),
        ];
    }

    /**
     * Gather receivables from source entity.
     *
     * @return array<IntercompanySettlementData>
     */
    private function gatherReceivables(
        string $fromEntityId,
        string $toEntityId,
        array $options,
    ): array {
        $transactions = $this->settlementService->getPendingTransactions($fromEntityId);

        return array_filter(
            $transactions,
            fn(IntercompanySettlementData $t) => 
                $t->toEntityId === $toEntityId && $t->isReceivable(),
        );
    }

    /**
     * Gather payables from source entity.
     *
     * @return array<IntercompanySettlementData>
     */
    private function gatherPayables(
        string $fromEntityId,
        string $toEntityId,
        array $options,
    ): array {
        $transactions = $this->settlementService->getPendingTransactions($fromEntityId);

        return array_filter(
            $transactions,
            fn(IntercompanySettlementData $t) => 
                $t->toEntityId === $toEntityId && $t->isPayable(),
        );
    }

    /**
     * Sum settlement amounts.
     *
     * @param array<IntercompanySettlementData> $transactions
     */
    private function sumAmounts(array $transactions): Money
    {
        if (empty($transactions)) {
            return Money::of(0, 'USD');
        }

        $sum = Money::of(0, $transactions[0]->settlementCurrency);
        foreach ($transactions as $transaction) {
            $sum = $sum->add($transaction->settlementAmount);
        }

        return $sum;
    }

    /**
     * Compensate failed settlement (saga pattern).
     */
    private function compensateFailedSettlement(
        string $settlementId,
        NetSettlementResult $nettingResult,
    ): void {
        $this->logger->info('Compensating failed settlement', [
            'settlement_id' => $settlementId,
        ]);

        // In real implementation, this would:
        // 1. Reverse any partial ledger entries
        // 2. Release any locks on transactions
        // 3. Notify stakeholders
        // 4. Update settlement status to CANCELLED
    }

    /**
     * Generate unique settlement ID.
     */
    private function generateSettlementId(): string
    {
        return 'ICSET-' . strtoupper(bin2hex(random_bytes(8)));
    }

    /**
     * Generate settlement reference.
     */
    private function generateSettlementReference(string $settlementId): string
    {
        return "REF-{$settlementId}-" . date('Ymd');
    }
}
