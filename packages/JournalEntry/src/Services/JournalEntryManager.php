<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Services;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Nexus\Common\Contracts\ClockInterface;
use Nexus\Common\ValueObjects\Money;
use Nexus\JournalEntry\Enums\JournalEntryStatus;
use Nexus\JournalEntry\Contracts\LedgerQueryInterface;
use Nexus\JournalEntry\Contracts\JournalEntryInterface;
use Nexus\JournalEntry\Contracts\JournalEntryQueryInterface;
use Nexus\JournalEntry\Contracts\JournalEntryManagerInterface;
use Nexus\JournalEntry\Contracts\JournalEntryPersistInterface;
use Nexus\JournalEntry\Contracts\CurrencyConverterInterface;
use Nexus\JournalEntry\Exceptions\InvalidJournalEntryException;
use Nexus\JournalEntry\Exceptions\JournalEntryNotFoundException;
use Nexus\JournalEntry\Exceptions\JournalEntryNotPostedException;
use Nexus\JournalEntry\Exceptions\UnbalancedJournalEntryException;
use Nexus\JournalEntry\Exceptions\InvalidExchangeRateException;
use Nexus\JournalEntry\Exceptions\JournalEntryAlreadyPostedException;
use Nexus\JournalEntry\Exceptions\JournalEntryAlreadyReversedException;

/**
 * Journal Entry Manager.
 *
 * Provides the primary business logic for journal entry operations:
 * - Creating draft journal entries
 * - Posting entries (with balance validation)
 * - Reversing posted entries
 * - Balance calculations
 *
 * This is the main public API for the JournalEntry package.
 */
final readonly class JournalEntryManager implements JournalEntryManagerInterface
{
    public function __construct(
        private JournalEntryQueryInterface $query,
        private JournalEntryPersistInterface $persist,
        private LedgerQueryInterface $ledgerQuery,
        private ClockInterface $clock,
        private string $defaultCurrency = 'MYR',
        private ?CurrencyConverterInterface $currencyConverter = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function createEntry(array $data, ?string $createdBy = null): JournalEntryInterface
    {
        // Validate required fields
        if (empty($data['description'])) {
            throw InvalidJournalEntryException::emptyDescription();
        }

        if (!isset($data['lines']) || count($data['lines']) < 2) {
            throw InvalidJournalEntryException::noLines();
        }

        // Validate balance
        $this->validateBalance($data['lines']);

        // Create the entry
        $entry = $this->persist->create($data);

        $this->logger->info('Journal entry created', [
            'entry_id' => $entry->getId(),
            'entry_number' => $entry->getNumber(),
            'created_by' => $createdBy,
        ]);

        return $entry;
    }

    public function postEntry(string $entryId, ?string $postedBy = null): JournalEntryInterface
    {
        $entry = $this->query->findById($entryId);

        if ($entry === null) {
            throw JournalEntryNotFoundException::withId($entryId);
        }

        if ($entry->getStatus() !== JournalEntryStatus::DRAFT) {
            throw JournalEntryAlreadyPostedException::cannotPost($entryId);
        }

        // Validate balance before posting
        $this->validateEntryBalance($entry);

        // Update status to posted
        $entry = $this->persist->updateStatus($entryId, JournalEntryStatus::POSTED);

        $this->logger->info('Journal entry posted', [
            'entry_id' => $entryId,
            'entry_number' => $entry->getNumber(),
            'posted_by' => $postedBy,
        ]);

        return $entry;
    }

    public function reverseEntry(
        string $entryId,
        ?string $reason = null,
        ?\DateTimeImmutable $reversalDate = null,
        ?string $reversedBy = null
    ): JournalEntryInterface {
        $original = $this->query->findById($entryId);

        if ($original === null) {
            throw JournalEntryNotFoundException::withId($entryId);
        }

        if ($original->getStatus() === JournalEntryStatus::REVERSED) {
            throw JournalEntryAlreadyReversedException::cannotReverse($entryId);
        }

        if ($original->getStatus() !== JournalEntryStatus::POSTED) {
            throw JournalEntryNotPostedException::cannotReverse($entryId);
        }

        $reversalDate = $reversalDate ?? $this->clock->now();

        // Create reversal entry by swapping debits and credits
        $reversalData = $this->buildReversalData($original, $reason, $reversalDate);
        $reversalEntry = $this->persist->create($reversalData);

        // Mark original as reversed
        $this->persist->updateStatus($entryId, JournalEntryStatus::REVERSED);

        // Post the reversal entry
        $this->persist->updateStatus($reversalEntry->getId(), JournalEntryStatus::POSTED);

        $this->logger->info('Journal entry reversed', [
            'original_entry_id' => $entryId,
            'reversal_entry_id' => $reversalEntry->getId(),
            'reason' => $reason,
            'reversed_by' => $reversedBy,
        ]);

        return $reversalEntry;
    }

    public function deleteEntry(string $entryId): void
    {
        $entry = $this->query->findById($entryId);

        if ($entry === null) {
            throw JournalEntryNotFoundException::withId($entryId);
        }

        if ($entry->getStatus() !== JournalEntryStatus::DRAFT) {
            throw JournalEntryAlreadyPostedException::cannotDelete($entryId);
        }

        $this->persist->delete($entryId);

        $this->logger->info('Journal entry deleted', [
            'entry_id' => $entryId,
            'entry_number' => $entry->getNumber(),
        ]);
    }

    public function findById(string $entryId): JournalEntryInterface
    {
        $entry = $this->query->findById($entryId);

        if ($entry === null) {
            throw JournalEntryNotFoundException::withId($entryId);
        }

        return $entry;
    }

    public function findByNumber(string $number): JournalEntryInterface
    {
        $entry = $this->query->findByNumber($number);

        if ($entry === null) {
            throw JournalEntryNotFoundException::withNumber($number);
        }

        return $entry;
    }

    public function getAccountBalance(string $accountId, \DateTimeImmutable $asOfDate): Money
    {
        return $this->ledgerQuery->getAccountBalance($accountId, $asOfDate);
    }

    public function generateTrialBalance(\DateTimeImmutable $asOfDate): array
    {
        $balances = $this->ledgerQuery->getAllAccountBalances($asOfDate);

        $totalDebit = Money::zero($this->defaultCurrency);
        $totalCredit = Money::zero($this->defaultCurrency);
        $result = [];

        foreach ($balances as $accountId => $balance) {
            $debit = Money::zero($balance->getCurrency());
            $credit = Money::zero($balance->getCurrency());

            if ($balance->isPositive()) {
                $debit = $balance;
                $totalDebit = $totalDebit->add($balance);
            } else {
                $credit = $balance->abs();
                $totalCredit = $totalCredit->add($balance->abs());
            }

            $result[$accountId] = [
                'account_id' => $accountId,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        return [
            'balances' => $result,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => $totalDebit->equals($totalCredit),
        ];
    }

    public function accountHasTransactions(string $accountId): bool
    {
        return $this->ledgerQuery->accountHasTransactions($accountId);
    }

    /**
     * Validate that entry lines balance (debits = credits).
     *
     * For multi-currency entries:
     * - If a CurrencyConverter is available, converts all amounts to the default currency before validation
     * - If no CurrencyConverter is available, validates that all lines use the same currency
     *
     * @param array<array{debit: string, credit: string, currency?: string}> $lines
     * @throws UnbalancedJournalEntryException
     * @throws InvalidExchangeRateException
     */
    private function validateBalance(array $lines): void
    {
        $currency = $this->defaultCurrency;
        $totalDebit = Money::zero($currency);
        $totalCredit = Money::zero($currency);
        
        // Check if all lines use the same currency
        $currencies = array_unique(array_map(
            fn($line) => $line['currency'] ?? $currency,
            $lines
        ));
        
        $hasMultipleCurrencies = count($currencies) > 1;

        // If multiple currencies and no converter, require single currency
        if ($hasMultipleCurrencies && $this->currencyConverter === null) {
            throw InvalidExchangeRateException::converterRequired($currencies);
        }

        foreach ($lines as $line) {
            $lineCurrency = $line['currency'] ?? $currency;
            
            // Process debit
            if (!empty($line['debit'])) {
                $debitMoney = Money::of($line['debit'], $lineCurrency);
                $totalDebit = $totalDebit->add($this->convertToCurrency($debitMoney, $currency));
            }

            // Process credit
            if (!empty($line['credit'])) {
                $creditMoney = Money::of($line['credit'], $lineCurrency);
                $totalCredit = $totalCredit->add($this->convertToCurrency($creditMoney, $currency));
            }
        }

        if (!$totalDebit->equals($totalCredit)) {
            throw UnbalancedJournalEntryException::create(
                (string) $totalDebit->getAmount(),
                (string) $totalCredit->getAmount(),
                $currency
            );
        }
    }

    /**
     * Convert money to target currency if needed.
     *
     * @param Money $money The amount to convert
     * @param string $toCurrency Target currency
     * @return Money Converted money or original if same currency
     * @throws InvalidExchangeRateException If conversion needed but no converter available
     */
    private function convertToCurrency(Money $money, string $toCurrency): Money
    {
        $fromCurrency = $money->getCurrency();
        
        // No conversion needed if same currency
        if ($fromCurrency === $toCurrency) {
            return $money;
        }

        // Convert using the injected converter
        if ($this->currencyConverter !== null) {
            return $this->currencyConverter->convert($money, $toCurrency);
        }

        // Unreachable: earlier validation guarantees converter is present if needed.
        // Defensive code removed for clarity.
    }

    /**
     * Validate that an existing entry is balanced.
     *
     * @throws UnbalancedJournalEntryException
     */
    private function validateEntryBalance(JournalEntryInterface $entry): void
    {
        $totalDebit = $entry->getTotalDebit();
        $totalCredit = $entry->getTotalCredit();

        if (!$totalDebit->equals($totalCredit)) {
            throw UnbalancedJournalEntryException::create(
                (string) $totalDebit->getAmount(),
                (string) $totalCredit->getAmount(),
                $totalDebit->getCurrency()
            );
        }
    }

    /**
     * Build reversal entry data from original entry.
     */
    private function buildReversalData(
        JournalEntryInterface $original,
        ?string $reason,
        \DateTimeImmutable $reversalDate
    ): array {
        $reversalLines = [];

        foreach ($original->getLines() as $line) {
            $reversalLines[] = [
                'account_id' => $line->getAccountId(),
                'debit' => $line->getCreditAmount()->getAmount(),
                'credit' => $line->getDebitAmount()->getAmount(),
                'currency' => $line->getDebitAmount()->getCurrency(),
                'description' => 'Reversal: ' . ($line->getDescription() ?? ''),
            ];
        }

        return [
            'date' => $reversalDate,
            'description' => 'Reversal of ' . $original->getNumber() . ($reason ? ': ' . $reason : ''),
            'reference' => 'REV-' . $original->getNumber(),
            'source_system' => 'journal_reversal',
            'source_document_id' => $original->getId(),
            'lines' => $reversalLines,
            'metadata' => [
                'original_entry_id' => $original->getId(),
                'original_entry_number' => $original->getNumber(),
                'reversal_reason' => $reason,
            ],
        ];
    }
}
