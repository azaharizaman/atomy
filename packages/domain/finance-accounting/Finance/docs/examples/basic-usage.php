<?php

declare(strict_types=1);

/**
 * Basic Usage Examples: Finance
 * 
 * Demonstrates:
 * 1. Creating chart of accounts
 * 2. Posting journal entries
 * 3. Getting account balances
 * 4. Trial balance generation
 */

use Nexus\Finance\Contracts\FinanceManagerInterface;
use Nexus\Finance\Enums\{AccountType, JournalEntryStatus};
use Nexus\Finance\ValueObjects\{AccountCode, Money, JournalEntryNumber};

// Example 1: Create Chart of Accounts
$financeManager = app(FinanceManagerInterface::class);

$cash = $financeManager->createAccount(
    code: new AccountCode('1110'),
    name: 'Cash',
    type: AccountType::Asset
);

$revenue = $financeManager->createAccount(
    code: new AccountCode('4100'),
    name: 'Sales Revenue',
    type: AccountType::Revenue
);

// Example 2: Post Journal Entry
$entry = new JournalEntry([
    'number' => new JournalEntryNumber('JE-001'),
    'entry_date' => now(),
    'description' => 'Customer payment',
    'lines' => [
        ['account_id' => $cash->getId(), 'debit' => 1000, 'credit' => 0],
        ['account_id' => $revenue->getId(), 'debit' => 0, 'credit' => 1000],
    ],
]);

$financeManager->postJournalEntry($entry);

// Example 3: Get Balance
$balance = $financeManager->getAccountBalance($cash->getId());
echo $balance->format(); // "MYR 1,000.00"
