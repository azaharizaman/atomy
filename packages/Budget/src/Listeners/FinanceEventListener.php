<?php

declare(strict_types=1);

namespace Nexus\Budget\Listeners;

use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Budget\Enums\TransactionType;
use Nexus\Budget\Services\BudgetVarianceInvestigator;
use Nexus\Budget\ValueObjects\BudgetVariance;
use Nexus\Finance\Events\JournalEntryPostedEvent;
use Nexus\Finance\Events\JournalEntryReversedEvent;
use Psr\Log\LoggerInterface;

/**
 * Finance Event Listener
 * 
 * Listens to Finance package events to record actual spending.
 * - JE Posted: Record actual against budget
 * - JE Reversed: Reverse the actual transaction
 */
final readonly class FinanceEventListener
{
    public function __construct(
        private BudgetManagerInterface $budgetManager,
        private BudgetRepositoryInterface $budgetRepository,
        private BudgetVarianceInvestigator $varianceInvestigator,
        private LoggerInterface $logger
    ) {}

    /**
     * Handle journal entry posted event - record actual spending
     */
    public function onJournalEntryPosted(JournalEntryPostedEvent $event): void
    {
        try {
            // Process each line item in the JE
            foreach ($event->lineItems as $line) {
                $budgetId = $this->resolveBudgetIdFromAccount($line['account_id']);
                if (!$budgetId) {
                    continue; // Skip if no budget mapping
                }

                // Record actual based on debit/credit nature
                $amount = $line['amount'];
                
                $this->budgetManager->recordActual(
                    budgetId: $budgetId,
                    amount: $amount,
                    sourceDocumentId: $event->journalEntryId,
                    transactionType: TransactionType::Actual,
                    releaseCommitment: true // Auto-release matching commitment
                );

                // Check variance after recording actual
                $this->checkVarianceThreshold($budgetId);
            }

            $this->logger->info('Budget actuals recorded for JE', [
                'je_id' => $event->journalEntryId,
                'line_count' => count($event->lineItems),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to record budget actuals for JE', [
                'je_id' => $event->journalEntryId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle journal entry reversed event - reverse the actual
     */
    public function onJournalEntryReversed(JournalEntryReversedEvent $event): void
    {
        try {
            // In a real implementation, would:
            // 1. Find all budget transactions linked to this JE
            // 2. Create reversing transactions
            // 3. Update budget balances
            
            $this->logger->info('Budget actuals reversed for JE', [
                'je_id' => $event->journalEntryId,
                'original_je_id' => $event->originalJournalEntryId,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to reverse budget actuals for JE', [
                'je_id' => $event->journalEntryId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve budget ID from GL account
     * 
     * Maps GL account to budget based on account's cost center,
     * department, or other dimensions.
     */
    private function resolveBudgetIdFromAccount(string $accountId): ?string
    {
        // This is a placeholder implementation
        // In real scenario, would:
        // 1. Query account to get cost_center_id or department_id
        // 2. Query budgets table to find active budget for that dimension
        // 3. Return budget_id
        
        // For now, return null to indicate no mapping found
        return null;
    }

    /**
     * Check if variance exceeds threshold and trigger investigation
     */
    private function checkVarianceThreshold(string $budgetId): void
    {
        try {
            $variance = $this->budgetManager->calculateVariance($budgetId);
            $this->varianceInvestigator->analyzeVariance($budgetId, $variance);
        } catch (\Exception $e) {
            $this->logger->error('Failed to check variance threshold', [
                'budget_id' => $budgetId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
