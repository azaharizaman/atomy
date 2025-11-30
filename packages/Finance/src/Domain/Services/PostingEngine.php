<?php

declare(strict_types=1);

namespace Nexus\Finance\Domain\Services;

use DateTimeImmutable;
use Nexus\Finance\Domain\Contracts\AccountRepositoryInterface;
use Nexus\Finance\Domain\Contracts\JournalEntryInterface;
use Nexus\Finance\Domain\Exceptions\InvalidAccountException;
use Nexus\Finance\Domain\Exceptions\UnbalancedJournalEntryException;
use Nexus\Period\Contracts\PeriodManagerInterface;
use Nexus\Period\Enums\PeriodType;

/**
 * Posting Engine
 * 
 * Domain service for validating and posting journal entries to the general ledger.
 */
final readonly class PostingEngine
{
    public function __construct(
        private PeriodManagerInterface $periodManager,
        private AccountRepositoryInterface $accountRepository
    ) {}

    /**
     * Validate a journal entry before posting
     * 
     * @throws UnbalancedJournalEntryException
     * @throws InvalidAccountException
     * @throws \Nexus\Period\Exceptions\PostingPeriodClosedException
     */
    public function validate(JournalEntryInterface $entry): void
    {
        // Check if entry is balanced
        if (!$entry->isBalanced()) {
            throw UnbalancedJournalEntryException::create(
                $entry->getTotalDebit(),
                $entry->getTotalCredit()
            );
        }

        // Check if all accounts exist and are postable
        foreach ($entry->getLines() as $line) {
            $account = $this->accountRepository->find($line->getAccountId());
            
            if ($account === null) {
                throw InvalidAccountException::invalidData(
                    "Account ID {$line->getAccountId()} does not exist"
                );
            }

            if ($account->isHeader()) {
                throw InvalidAccountException::headerAccountNotPostable($account->getCode());
            }

            if (!$account->isActive()) {
                throw InvalidAccountException::invalidData(
                    "Account {$account->getCode()} is inactive"
                );
            }
        }

        // Check if posting period is open
        $this->periodManager->isPostingAllowed($entry->getDate(), PeriodType::Accounting);
    }

    /**
     * Post a journal entry
     * 
     * This method performs the actual posting logic. In a real implementation,
     * this would update account balances or create ledger entries.
     * 
     * For now, validation is the main concern. The repository layer handles persistence.
     */
    public function post(JournalEntryInterface $entry): void
    {
        $this->validate($entry);
        
        // The actual posting to ledger tables would happen here
        // For now, the repository handles updating the entry status to "posted"
    }

    /**
     * Calculate the impact of a journal entry on accounts
     * 
     * @return array<string, array{debit: string, credit: string}>
     */
    public function calculateImpact(JournalEntryInterface $entry): array
    {
        $impact = [];

        foreach ($entry->getLines() as $line) {
            $accountId = $line->getAccountId();

            if (!isset($impact[$accountId])) {
                $impact[$accountId] = ['debit' => '0.0000', 'credit' => '0.0000'];
            }

            $impact[$accountId]['debit'] = bcadd(
                $impact[$accountId]['debit'],
                $line->getDebitAmount()->getAmount(),
                4
            );

            $impact[$accountId]['credit'] = bcadd(
                $impact[$accountId]['credit'],
                $line->getCreditAmount()->getAmount(),
                4
            );
        }

        return $impact;
    }
}
