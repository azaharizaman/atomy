<?php

declare(strict_types=1);

/**
 * Advanced Usage: Multi-Currency Journal Entry
 */

use Nexus\Finance\Domain\Contracts\FinanceManagerInterface;
use Nexus\Finance\Domain\ValueObjects\{Money, ExchangeRate};

// Example: Obtain the FinanceManagerInterface from your service container
// This is framework-agnostic - use your DI container's method
// Laravel: $financeManager = app(FinanceManagerInterface::class);
// Symfony: $financeManager = $container->get(FinanceManagerInterface::class);

// Multi-currency journal entry example:
// Receive USD payment, convert to MYR
// $entry = $financeManager->createJournalEntry([
//     'date' => new DateTimeImmutable(),
//     'description' => 'USD payment received',
//     'lines' => [
//         [
//             'account_id' => $cashUsd->getId(),
//             'debit' => '1000.0000',
//             'credit' => '0.0000',
//             'currency' => 'USD',
//             // Exchange rate can be handled via ExchangeRate value object
//         ],
//         [
//             'account_id' => $accountsReceivable->getId(),
//             'debit' => '0.0000',
//             'credit' => '4750.0000', // 1000 × 4.75 MYR/USD
//             'currency' => 'MYR',
//         ],
//     ],
// ]);

// $financeManager->postJournalEntry($entry->getId());
