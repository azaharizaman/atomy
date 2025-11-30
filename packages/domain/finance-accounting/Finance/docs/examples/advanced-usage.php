<?php

declare(strict_types=1);

/**
 * Advanced Usage: Multi-Currency Journal Entry
 */

use Nexus\Finance\Contracts\FinanceManagerInterface;
use Nexus\Finance\ValueObjects\{Money, ExchangeRate};

$financeManager = app(FinanceManagerInterface::class);

// Receive USD payment, convert to MYR
$entry = new JournalEntry([
    'number' => new JournalEntryNumber('JE-002'),
    'entry_date' => now(),
    'description' => 'USD payment received',
    'lines' => [
        [
            'account_id' => $cashUsd->getId(),
            'debit' => new Money(1000, 'USD'),
            'credit' => new Money(0, 'USD'),
            'exchange_rate' => new ExchangeRate(4.75), // MYR/USD
        ],
        [
            'account_id' => $accountsReceivable->getId(),
            'debit' => new Money(0, 'MYR'),
            'credit' => new Money(4750, 'MYR'), // 1000 × 4.75
        ],
    ],
]);

$financeManager->postJournalEntry($entry);
