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

use Nexus\Finance\Domain\Contracts\FinanceManagerInterface;
use Nexus\Finance\Domain\Enums\{AccountType, JournalEntryStatus};
use Nexus\Finance\Domain\ValueObjects\{AccountCode, Money, JournalEntryNumber};

// Example: Obtain the FinanceManagerInterface from your service container
// This is framework-agnostic - use your DI container's method
// Laravel: $financeManager = app(FinanceManagerInterface::class);
// Symfony: $financeManager = $container->get(FinanceManagerInterface::class);

// Create accounts (consumer application provides implementation)
// $cash = $financeManager->createAccount([
//     'code' => '1110',
//     'name' => 'Cash',
//     'type' => AccountType::Asset->value,
//     'currency' => 'MYR',
//     'is_header' => false,
//     'is_active' => true,
// ]);

// $revenue = $financeManager->createAccount([
//     'code' => '4100',
//     'name' => 'Sales Revenue',
//     'type' => AccountType::Revenue->value,
//     'currency' => 'MYR',
//     'is_header' => false,
//     'is_active' => true,
// ]);

// Example 2: Post Journal Entry
// Note: Consumer application provides concrete JournalEntry implementation
// $entry = $financeManager->createJournalEntry([
//     'date' => new DateTimeImmutable(),
//     'description' => 'Customer payment',
//     'lines' => [
//         ['account_id' => $cash->getId(), 'debit' => '1000.0000', 'credit' => '0.0000'],
//         ['account_id' => $revenue->getId(), 'debit' => '0.0000', 'credit' => '1000.0000'],
//     ],
// ]);

// $financeManager->postJournalEntry($entry->getId());

// Example 3: Get Balance
// $balance = $financeManager->getAccountBalance($cash->getId(), new DateTimeImmutable());
// echo $balance; // "1000.0000"
