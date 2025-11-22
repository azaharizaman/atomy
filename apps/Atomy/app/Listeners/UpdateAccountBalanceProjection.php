<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\QueryException;
use Nexus\Finance\Events\AccountDebitedEvent;
use Nexus\Finance\Events\AccountCreditedEvent;
use App\Models\Finance\AccountBalanceProjection;
use App\Models\Finance\AccountBalanceSnapshot;
use Psr\Log\LoggerInterface;

/**
 * Update Account Balance Projection (Async)
 * 
 * Listens to AccountDebitedEvent and AccountCreditedEvent.
 * Updates the materialized view with optimistic locking.
 * 
 * Features:
 * - Queued processing on finance-projections queue
 * - Optimistic locking via updated_at version check
 * - Hot account tracking with Redis ZINCRBY
 * - Dynamic snapshot creation based on thresholds
 * - Retry logic with exponential backoff
 */
final class UpdateAccountBalanceProjection implements ShouldQueue
{
    use InteractsWithQueue;
    
    /**
     * Queue connection and name
     */
    public string $connection = 'redis';
    public string $queue = 'finance-projections';
    
    /**
     * Retry configuration
     */
    public int $tries = 3;
    public int $backoff = 5; // seconds
    
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}
    
    /**
     * Handle AccountDebitedEvent or AccountCreditedEvent
     */
    public function handle(AccountDebitedEvent|AccountCreditedEvent $event): void
    {
        $maxRetries = 3;
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                DB::transaction(function () use ($event) {
                    $this->updateProjection($event);
                });
                
                // Success - update hot account tracking
                $this->trackHotAccount($event->accountId);
                
                $this->logger->info('Projection updated', [
                    'event_id' => $event->getEventId(),
                    'account_id' => $event->accountId,
                    'event_type' => $event instanceof AccountDebitedEvent ? 'debit' : 'credit',
                    'amount' => $event->amount->getAmount(),
                ]);
                
                return;
            } catch (QueryException $e) {
                // Optimistic locking conflict - retry
                if ($e->getCode() === '40001' || str_contains($e->getMessage(), 'could not serialize access')) {
                    $attempt++;
                    
                    if ($attempt >= $maxRetries) {
                        $this->logger->error('Projection update failed after retries', [
                            'event_id' => $event->getEventId(),
                            'account_id' => $event->accountId,
                            'attempts' => $attempt,
                            'error' => $e->getMessage(),
                        ]);
                        
                        throw $e;
                    }
                    
                    // Exponential backoff
                    usleep(100000 * (2 ** $attempt)); // 100ms, 200ms, 400ms
                    continue;
                }
                
                throw $e;
            }
        }
    }
    
    /**
     * Update projection with optimistic locking
     */
    private function updateProjection(AccountDebitedEvent|AccountCreditedEvent $event): void
    {
        $projection = AccountBalanceProjection::where('account_id', $event->accountId)
            ->lockForUpdate()
            ->first();
        
        if (!$projection) {
            // First event for this account - create projection
            $projection = AccountBalanceProjection::create([
                'tenant_id' => $event->getTenantId(),
                'account_id' => $event->accountId,
                'debit_balance' => '0.0000',
                'credit_balance' => '0.0000',
                'current_balance' => '0.0000',
                'last_event_version' => 0,
                'access_count' => 0,
            ]);
        }
        
        // Idempotency check - skip if already processed
        if ($event->getVersion() <= $projection->last_event_version) {
            return;
        }
        
        // Store original updated_at for optimistic locking
        $originalUpdatedAt = $projection->updated_at;
        
        // Update balance
        if ($event instanceof AccountDebitedEvent) {
            $projection->addDebit($event->amount->getAmount());
        } else {
            $projection->addCredit($event->amount->getAmount());
        }
        
        // Update event tracking
        $projection->last_event_version = $event->getVersion();
        $projection->last_event_at = $event->getOccurredAt();
        
        // Optimistic locking check
        $updated = DB::table('account_balance_projections')
            ->where('id', $projection->id)
            ->where('updated_at', $originalUpdatedAt)
            ->update([
                'debit_balance' => $projection->debit_balance,
                'credit_balance' => $projection->credit_balance,
                'current_balance' => $projection->current_balance,
                'last_event_version' => $projection->last_event_version,
                'last_event_at' => $projection->last_event_at,
                'updated_at' => now(),
            ]);
        
        if ($updated === 0) {
            throw new QueryException(
                'PostgreSQL',
                'UPDATE account_balance_projections',
                [],
                new \Exception('Optimistic locking conflict - could not serialize access')
            );
        }
        
        // Check if snapshot should be created
        $this->maybeCreateSnapshot($projection, $event->getVersion());
    }
    
    /**
     * Create snapshot if threshold exceeded
     */
    private function maybeCreateSnapshot(AccountBalanceProjection $projection, int $eventVersion): void
    {
        $latestSnapshot = AccountBalanceSnapshot::where('account_id', $projection->account_id)
            ->orderByDesc('event_version')
            ->first();
        
        if (!$latestSnapshot) {
            // Create first snapshot after 100 events
            if ($eventVersion >= 100) {
                AccountBalanceSnapshot::createFromProjection($projection, $eventVersion);
            }
            return;
        }
        
        // Increment events counter
        $latestSnapshot->events_since_snapshot++;
        
        // Adjust threshold based on activity
        $latestSnapshot->adjustThreshold($projection->access_count);
        
        // Create new snapshot if threshold exceeded
        if ($latestSnapshot->shouldCreateSnapshot()) {
            AccountBalanceSnapshot::createFromProjection($projection, $eventVersion);
        } else {
            $latestSnapshot->save();
        }
    }
    
    /**
     * Track hot account access in Redis sorted set
     */
    private function trackHotAccount(string $accountId): void
    {
        try {
            Redis::connection('hot-accounts')
                ->zincrby('hot_accounts', 1, $accountId);
        } catch (\Exception $e) {
            // Non-critical - log and continue
            $this->logger->warning('Failed to update hot account tracking', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
