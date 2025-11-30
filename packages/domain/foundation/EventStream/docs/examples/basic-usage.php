<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Publishing and Reading Events
 * 
 * This example shows the most basic usage of EventStream:
 * 1. Define a domain event
 * 2. Publish event to stream
 * 3. Read events from stream
 */

use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;

// ============================================
// Step 1: Define a Domain Event
// ============================================

readonly class AccountCreditedEvent implements EventInterface
{
    public function __construct(
        public string $accountId,
        public int $amount,
        public string $journalEntryId,
        public string $description,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {}

    public function getEventType(): string
    {
        return 'AccountCredited';
    }

    public function getAggregateId(): string
    {
        return $this->accountId;
    }

    public function getPayload(): array
    {
        return [
            'account_id' => $this->accountId,
            'amount' => $this->amount,
            'journal_entry_id' => $this->journalEntryId,
            'description' => $this->description,
        ];
    }

    public function getMetadata(): array
    {
        return [
            'tenant_id' => 'tenant-123',
            'user_id' => 'user-456',
        ];
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}

// ============================================
// Step 2: Publish Events to Stream
// ============================================

class BankAccountService
{
    public function __construct(
        private readonly EventStoreInterface $eventStore
    ) {}

    public function deposit(string $accountId, int $amount, string $description): void
    {
        // Create event
        $event = new AccountCreditedEvent(
            accountId: $accountId,
            amount: $amount,
            journalEntryId: 'JE-' . uniqid(),
            description: $description
        );

        // Publish to event stream
        $this->eventStore->append($accountId, $event);
        
        echo "✅ Deposited {$amount} to account {$accountId}\n";
    }

    public function withdraw(string $accountId, int $amount, string $description): void
    {
        // Similar to deposit but with AccountDebitedEvent
        // ... implementation
    }
}

// ============================================
// Step 3: Read Events from Stream
// ============================================

class AccountBalanceQuery
{
    public function __construct(
        private readonly StreamReaderInterface $streamReader
    ) {}

    public function getCurrentBalance(string $accountId): int
    {
        // Read all events for this account
        $events = $this->streamReader->readStream($accountId);

        // Calculate balance from events
        $balance = 0;
        foreach ($events as $event) {
            if ($event instanceof AccountCreditedEvent) {
                $balance += $event->amount;
            } elseif ($event instanceof AccountDebitedEvent) {
                $balance -= $event->amount;
            }
        }

        return $balance;
    }

    public function getTransactionHistory(string $accountId): array
    {
        // Read all events
        $events = $this->streamReader->readStream($accountId);

        // Format for display
        return array_map(function ($event) {
            return [
                'date' => $event->getOccurredAt()->format('Y-m-d H:i:s'),
                'type' => $event->getEventType(),
                'amount' => $event->amount,
                'description' => $event->description,
            ];
        }, $events);
    }
}

// ============================================
// Usage Example
// ============================================

// Assume $eventStore and $streamReader are injected via DI

$accountService = new BankAccountService($eventStore);
$balanceQuery = new AccountBalanceQuery($streamReader);

// Make some transactions
$accountService->deposit('account-1000', 5000, 'Initial deposit');
$accountService->deposit('account-1000', 2500, 'Second deposit');
$accountService->withdraw('account-1000', 1000, 'ATM withdrawal');

// Query current balance
$balance = $balanceQuery->getCurrentBalance('account-1000');
echo "Current balance: {$balance}\n"; // Output: 6500

// Get transaction history
$history = $balanceQuery->getTransactionHistory('account-1000');
print_r($history);

/* Output:
Array
(
    [0] => Array
        (
            [date] => 2025-11-24 10:30:00
            [type] => AccountCredited
            [amount] => 5000
            [description] => Initial deposit
        )
    [1] => Array
        (
            [date] => 2025-11-24 10:31:00
            [type] => AccountCredited
            [amount] => 2500
            [description] => Second deposit
        )
    [2] => Array
        (
            [date] => 2025-11-24 10:32:00
            [type] => AccountDebited
            [amount] => 1000
            [description] => ATM withdrawal
        )
)
*/
