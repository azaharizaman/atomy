<?php

declare(strict_types=1);

namespace Nexus\Finance\Application\Handlers;

use DateTimeImmutable;
use Nexus\Finance\Domain\Contracts\AccountInterface;
use Nexus\Finance\Domain\Contracts\AccountRepositoryInterface;
use Nexus\Finance\Domain\Contracts\FinanceManagerInterface;
use Nexus\Finance\Domain\Contracts\JournalEntryInterface;
use Nexus\Finance\Domain\Contracts\JournalEntryRepositoryInterface;
use Nexus\Finance\Domain\Contracts\LedgerRepositoryInterface;
use Nexus\Finance\Domain\Enums\JournalEntryStatus;
use Nexus\Finance\Domain\Exceptions\AccountNotFoundException;
use Nexus\Finance\Domain\Exceptions\DuplicateAccountCodeException;
use Nexus\Finance\Domain\Exceptions\JournalEntryAlreadyPostedException;
use Nexus\Finance\Domain\Exceptions\JournalEntryNotFoundException;
use Nexus\Finance\Domain\Exceptions\JournalEntryNotPostedException;
use Nexus\Finance\Domain\Services\BalanceCalculator;
use Nexus\Finance\Domain\Services\PostingEngine;
use Nexus\Finance\Domain\ValueObjects\AccountCode;

/**
 * Finance Manager
 * 
 * Application service (handler) for general ledger and journal entry operations.
 */
final readonly class FinanceManager implements FinanceManagerInterface
{
    public function __construct(
        private JournalEntryRepositoryInterface $journalEntryRepository,
        private AccountRepositoryInterface $accountRepository,
        private LedgerRepositoryInterface $ledgerRepository,
        private PostingEngine $postingEngine,
        private BalanceCalculator $balanceCalculator
    ) {}

    /**
     * {@inheritDoc}
     */
    public function createJournalEntry(array $data): JournalEntryInterface
    {
        // Generate entry number if not provided
        $entryDate = isset($data['date']) ? new DateTimeImmutable($data['date']) : new DateTimeImmutable();
        
        if (!isset($data['entry_number'])) {
            $data['entry_number'] = $this->journalEntryRepository->getNextEntryNumber($entryDate);
        }

        // Set default status to Draft
        $data['status'] = $data['status'] ?? JournalEntryStatus::Draft->value;

        // This would create a new journal entry entity
        // Implementation depends on how we construct journal entries
        // For now, this is a placeholder that assumes the repository handles creation

        throw new \RuntimeException('createJournalEntry implementation depends on entity factory');
    }

    /**
     * {@inheritDoc}
     */
    public function postJournalEntry(string $journalEntryId): void
    {
        $entry = $this->findJournalEntry($journalEntryId);

        // Check if already posted
        if ($entry->isPosted()) {
            throw JournalEntryAlreadyPostedException::forEntry(
                $entry->getId(),
                $entry->getEntryNumber()
            );
        }

        // Validate and post using the posting engine
        $this->postingEngine->post($entry);

        // The repository should handle updating the status to Posted
        // and setting the posted_at timestamp
    }

    /**
     * {@inheritDoc}
     */
    public function reverseJournalEntry(
        string $journalEntryId,
        DateTimeImmutable $reversalDate,
        string $reason
    ): JournalEntryInterface {
        $originalEntry = $this->findJournalEntry($journalEntryId);

        // Check if posted
        if (!$originalEntry->isPosted()) {
            throw JournalEntryNotPostedException::forEntry($journalEntryId);
        }

        // Create reversal entry (swap debits and credits)
        $reversalData = [
            'date' => $reversalDate,
            'description' => "Reversal of {$originalEntry->getEntryNumber()}: {$reason}",
            'reference' => $originalEntry->getEntryNumber(),
            'lines' => [],
        ];

        foreach ($originalEntry->getLines() as $line) {
            $reversalData['lines'][] = [
                'account_id' => $line->getAccountId(),
                'debit' => $line->getCreditAmount()->getAmount(), // Swap!
                'credit' => $line->getDebitAmount()->getAmount(), // Swap!
                'description' => $line->getDescription(),
            ];
        }

        $reversalEntry = $this->createJournalEntry($reversalData);

        // Auto-post the reversal entry
        $this->postJournalEntry($reversalEntry->getId());

        return $reversalEntry;
    }

    /**
     * {@inheritDoc}
     */
    public function findJournalEntry(string $journalEntryId): JournalEntryInterface
    {
        $entry = $this->journalEntryRepository->find($journalEntryId);

        if ($entry === null) {
            throw JournalEntryNotFoundException::forId($journalEntryId);
        }

        return $entry;
    }

    /**
     * {@inheritDoc}
     */
    public function findAccount(string $accountId): AccountInterface
    {
        $account = $this->accountRepository->find($accountId);

        if ($account === null) {
            throw AccountNotFoundException::forId($accountId);
        }

        return $account;
    }

    /**
     * {@inheritDoc}
     */
    public function findAccountByCode(string $accountCode): AccountInterface
    {
        $account = $this->accountRepository->findByCode($accountCode);

        if ($account === null) {
            throw AccountNotFoundException::forCode($accountCode);
        }

        return $account;
    }

    /**
     * {@inheritDoc}
     */
    public function createAccount(array $data): AccountInterface
    {
        // Validate account code
        $code = AccountCode::fromString($data['code']);

        // Check for duplicates
        if ($this->accountRepository->codeExists($code->getValue())) {
            throw DuplicateAccountCodeException::forCode($code->getValue());
        }

        // This would create a new account entity
        // Implementation depends on how we construct accounts
        throw new \RuntimeException('createAccount implementation depends on entity factory');
    }

    /**
     * {@inheritDoc}
     */
    public function getAccountBalance(string $accountId, DateTimeImmutable $asOfDate): string
    {
        // Verify account exists
        $account = $this->findAccount($accountId);

        return $this->ledgerRepository->getAccountBalance($accountId, $asOfDate);
    }

    /**
     * {@inheritDoc}
     */
    public function listAccounts(array $filters = []): array
    {
        return $this->accountRepository->findAll($filters);
    }

    /**
     * {@inheritDoc}
     */
    public function listJournalEntries(array $filters = []): array
    {
        return $this->journalEntryRepository->findAll($filters);
    }
}
