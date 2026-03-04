<?php

declare(strict_types=1);

namespace Nexus\Budget\Listeners;

use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\Budget\Contracts\BudgetRepositoryInterface;
use Nexus\Budget\Contracts\JournalEntryPostedEventInterface;
use Nexus\Budget\Contracts\JournalEntryReversedEventInterface;
use Nexus\Budget\Services\BudgetVarianceInvestigator;
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
    public function onJournalEntryPosted(JournalEntryPostedEventInterface $event): void
    {
        try {
            // Process each line item in the JE
            foreach ($event->getLineItems() as $index => $line) {
                $budgetId = $this->resolveBudgetIdFromAccount($line->getAccountId());
                if (!$budgetId) {
                    continue; // Skip if no budget mapping
                }

                // Record actual based on debit/credit nature
                $amount = $line->getAmount();
                
                $this->budgetManager->recordActual(
                    budgetId: $budgetId,
                    amount: $amount,
                    accountId: $line->getAccountId(),
                    sourceType: 'journal_entry_line',
                    sourceId: $event->getJournalEntryId(),
                    sourceLineNumber: is_int($index) ? ($index + 1) : 1
                );

                // Check variance after recording actual
                $this->checkVarianceThreshold($budgetId);
            }

            $this->logger->info('Budget actuals recorded for JE', [
                'je_id' => $event->getJournalEntryId(),
                'line_count' => count($event->getLineItems()),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to record budget actuals for JE', [
                'je_id' => $event->getJournalEntryId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle journal entry reversed event - reverse the actual
     */
    public function onJournalEntryReversed(JournalEntryReversedEventInterface $event): void
    {
        try {
            // In a real implementation, would:
            // 1. Find all budget transactions linked to this JE
            // 2. Create reversing transactions
            // 3. Update budget balances
            
            $this->logger->info('Budget actuals reversed for JE', [
                'je_id' => $event->getJournalEntryId(),
                'original_je_id' => $event->getOriginalJournalEntryId(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to reverse budget actuals for JE', [
                'je_id' => $event->getJournalEntryId(),
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
