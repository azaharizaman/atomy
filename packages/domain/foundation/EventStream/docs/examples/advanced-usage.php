<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Event Sourcing with Snapshots and Temporal Queries
 * 
 * This example demonstrates:
 * 1. Event-sourced aggregates
 * 2. Snapshot optimization
 * 3. Temporal queries (time travel)
 * 4. Concurrency control
 */

use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\EventStream\Contracts\StreamReaderInterface;
use Nexus\EventStream\Contracts\SnapshotRepositoryInterface;
use Nexus\EventStream\Contracts\SnapshotInterface;
use Nexus\EventStream\Exceptions\ConcurrencyException;

// ============================================
// Step 1: Event-Sourced Aggregate
// ============================================

class BankAccount
{
    private string $accountId;
    private int $balance = 0;
    private int $version = 0;
    private array $uncommittedEvents = [];

    private function __construct(string $accountId)
    {
        $this->accountId = $accountId;
    }

    // Factory method: Create new account
    public static function open(string $accountId, int $initialDeposit): self
    {
        $account = new self($accountId);
        $account->recordThat(new AccountOpenedEvent($accountId, $initialDeposit));
        return $account;
    }

    // Factory method: Rebuild from events
    public static function fromEvents(string $accountId, array $events): self
    {
        $account = new self($accountId);
        foreach ($events as $event) {
            $account->apply($event);
        }
        return $account;
    }

    // Factory method: Rebuild from snapshot + recent events
    public static function fromSnapshot(SnapshotInterface $snapshot, array $events): self
    {
        $account = new self($snapshot->getAggregateId());
        $state = $snapshot->getState();
        
        $account->balance = $state['balance'];
        $account->version = $snapshot->getVersion();
        
        foreach ($events as $event) {
            $account->apply($event);
        }
        
        return $account;
    }

    // Command: Deposit money
    public function deposit(int $amount, string $description): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        $this->recordThat(new AccountCreditedEvent(
            accountId: $this->accountId,
            amount: $amount,
            journalEntryId: 'JE-' . uniqid(),
            description: $description
        ));
    }

    // Command: Withdraw money
    public function withdraw(int $amount, string $description): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        if ($this->balance < $amount) {
            throw new \DomainException('Insufficient funds');
        }

        $this->recordThat(new AccountDebitedEvent(
            accountId: $this->accountId,
            amount: $amount,
            journalEntryId: 'JE-' . uniqid(),
            description: $description
        ));
    }

    // Record event and apply to current state
    private function recordThat(EventInterface $event): void
    {
        $this->uncommittedEvents[] = $event;
        $this->apply($event);
    }

    // Apply event to rebuild state
    private function apply(EventInterface $event): void
    {
        match ($event::class) {
            AccountOpenedEvent::class => $this->balance = $event->amount,
            AccountCreditedEvent::class => $this->balance += $event->amount,
            AccountDebitedEvent::class => $this->balance -= $event->amount,
            default => null
        };
        
        $this->version++;
    }

    // Get uncommitted events for persistence
    public function getUncommittedEvents(): array
    {
        return $this->uncommittedEvents;
    }

    // Clear uncommitted events after save
    public function markEventsAsCommitted(): void
    {
        $this->uncommittedEvents = [];
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    // Create snapshot of current state
    public function createSnapshot(): SnapshotInterface
    {
        return new class($this->accountId, $this->balance, $this->version) implements SnapshotInterface {
            public function __construct(
                private readonly string $aggregateId,
                private readonly int $balance,
                private readonly int $version
            ) {}

            public function getAggregateId(): string
            {
                return $this->aggregateId;
            }

            public function getState(): array
            {
                return ['balance' => $this->balance];
            }

            public function getVersion(): int
            {
                return $this->version;
            }

            public function getCreatedAt(): \DateTimeImmutable
            {
                return new \DateTimeImmutable();
            }
        };
    }
}

// ============================================
// Step 2: Repository with Snapshot Support
// ============================================

class BankAccountRepository
{
    private const SNAPSHOT_INTERVAL = 100; // Create snapshot every 100 events

    public function __construct(
        private readonly EventStoreInterface $eventStore,
        private readonly StreamReaderInterface $streamReader,
        private readonly SnapshotRepositoryInterface $snapshotRepository
    ) {}

    public function save(BankAccount $account): void
    {
        $events = $account->getUncommittedEvents();
        
        if (empty($events)) {
            return;
        }

        try {
            // Append with optimistic concurrency check
            $expectedVersion = $account->getVersion() - count($events);
            
            $this->eventStore->appendBatch(
                $account->getAccountId(),
                $events,
                $expectedVersion
            );

            $account->markEventsAsCommitted();

            // Create snapshot if threshold reached
            if ($account->getVersion() % self::SNAPSHOT_INTERVAL === 0) {
                $this->snapshotRepository->store($account->createSnapshot());
            }

        } catch (ConcurrencyException $e) {
            throw new \RuntimeException(
                'Concurrent modification detected. Please reload and try again.',
                previous: $e
            );
        }
    }

    public function load(string $accountId): BankAccount
    {
        // Try to load from snapshot first
        $snapshot = $this->snapshotRepository->get($accountId);

        if ($snapshot) {
            // Load events since snapshot
            $events = $this->streamReader->readStreamFromVersion(
                $accountId,
                $snapshot->getVersion() + 1
            );
            return BankAccount::fromSnapshot($snapshot, $events);
        }

        // No snapshot, load all events
        $events = $this->streamReader->readStream($accountId);
        
        if (empty($events)) {
            throw new \DomainException("Account not found: {$accountId}");
        }

        return BankAccount::fromEvents($accountId, $events);
    }

    // Temporal query: Load account state at specific point in time
    public function loadAt(string $accountId, \DateTimeImmutable $timestamp): BankAccount
    {
        $events = $this->streamReader->readStreamUntil($accountId, $timestamp);
        
        if (empty($events)) {
            throw new \DomainException("Account did not exist at {$timestamp->format('Y-m-d')}");
        }

        return BankAccount::fromEvents($accountId, $events);
    }
}

// ============================================
// Step 3: Application Service
// ============================================

class BankAccountService
{
    public function __construct(
        private readonly BankAccountRepository $repository
    ) {}

    public function openAccount(string $accountId, int $initialDeposit): void
    {
        $account = BankAccount::open($accountId, $initialDeposit);
        $this->repository->save($account);
    }

    public function deposit(string $accountId, int $amount, string $description): void
    {
        $account = $this->repository->load($accountId);
        $account->deposit($amount, $description);
        $this->repository->save($account);
    }

    public function withdraw(string $accountId, int $amount, string $description): void
    {
        $account = $this->repository->load($accountId);
        $account->withdraw($amount, $description);
        $this->repository->save($account);
    }

    public function getBalance(string $accountId): int
    {
        $account = $this->repository->load($accountId);
        return $account->getBalance();
    }

    // Temporal query: Get balance at specific date
    public function getBalanceAt(string $accountId, \DateTimeImmutable $date): int
    {
        $account = $this->repository->loadAt($accountId, $date);
        return $account->getBalance();
    }
}

// ============================================
// Usage Example
// ============================================

// Initialize service
$service = new BankAccountService($repository);

// Open account
$service->openAccount('account-1000', 10000);

// Make transactions
$service->deposit('account-1000', 5000, 'Salary');
$service->withdraw('account-1000', 2000, 'Rent payment');
$service->deposit('account-1000', 1500, 'Freelance income');

// Get current balance
$currentBalance = $service->getBalance('account-1000');
echo "Current balance: {$currentBalance}\n"; // Output: 14500

// Time travel: Get balance as it was on 2025-11-01
$historicalBalance = $service->getBalanceAt(
    'account-1000',
    new \DateTimeImmutable('2025-11-01')
);
echo "Balance on 2025-11-01: {$historicalBalance}\n";

// ============================================
// Concurrency Control Example
// ============================================

try {
    // Two users trying to withdraw simultaneously
    $account1 = $repository->load('account-1000');
    $account2 = $repository->load('account-1000'); // Same version

    $account1->withdraw(3000, 'User A withdrawal');
    $repository->save($account1); // ✅ Success

    $account2->withdraw(2000, 'User B withdrawal');
    $repository->save($account2); // ❌ Throws ConcurrencyException
    
} catch (\RuntimeException $e) {
    echo "Error: {$e->getMessage()}\n";
    // Handle: reload account and retry
}

// ============================================
// Performance: Snapshot Benefits
// ============================================

/**
 * Without snapshots:
 * - Loading account with 10,000 events = read + deserialize 10,000 rows
 * - Time: ~2-5 seconds
 * 
 * With snapshots (every 100 events):
 * - Loading account with 10,000 events = read 1 snapshot + 0-99 recent events
 * - Time: ~50-100ms (20-50x faster!)
 */
