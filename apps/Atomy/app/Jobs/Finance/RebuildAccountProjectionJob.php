<?php

declare(strict_types=1);

namespace App\Jobs\Finance;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Models\Finance\AccountBalanceProjection;
use App\Models\Finance\AccountBalanceSnapshot;
use Psr\Log\LoggerInterface;

/**
 * Rebuild Account Projection Job
 * 
 * Rebuilds the AccountBalanceProjection for a single account by replaying
 * all AccountDebitedEvent and AccountCreditedEvent from the event stream.
 * 
 * Designed for parallel processing via worker pool.
 */
final class RebuildAccountProjectionJob implements ShouldQueue
{
    use Queueable;

    public string $connection = 'redis';
    public string $queue = 'finance-projections';
    public int $tries = 3;
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $accountId,
        private readonly bool $useSnapshot = true
    ) {}

    /**
     * Execute the job.
     */
    public function handle(LoggerInterface $logger): void
    {
        $startTime = microtime(true);

        // Find or create projection
        $projection = AccountBalanceProjection::firstOrNew(
            ['account_id' => $this->accountId]
        );

        // Start from snapshot if available and requested
        $startVersion = 0;
        if ($this->useSnapshot) {
            $snapshot = AccountBalanceSnapshot::where('account_id', $this->accountId)
                ->orderByDesc('event_version')
                ->first();

            if ($snapshot) {
                // Load snapshot state
                $projection->fill([
                    'debit_balance' => $snapshot->snapshot_data['debit_balance'],
                    'credit_balance' => $snapshot->snapshot_data['credit_balance'],
                    'current_balance' => $snapshot->snapshot_data['current_balance'],
                    'last_event_version' => $snapshot->event_version,
                ]);
                $startVersion = $snapshot->event_version;

                $logger->info("Starting rebuild from snapshot", [
                    'account_id' => $this->accountId,
                    'snapshot_version' => $startVersion,
                ]);
            }
        }

        // Query events from event_streams (after snapshot version)
        $events = DB::table('event_streams')
            ->where('aggregate_type', 'account')
            ->where('aggregate_id', $this->accountId)
            ->where('event_version', '>', $startVersion)
            ->orderBy('event_version')
            ->get(['event_type', 'payload', 'event_version', 'occurred_at']);

        $processedEvents = 0;

        // Replay events
        foreach ($events as $event) {
            $payload = json_decode($event->payload, true);

            if ($event->event_type === 'AccountDebitedEvent') {
                $projection->addDebit($payload['amount']);
            } elseif ($event->event_type === 'AccountCreditedEvent') {
                $projection->addCredit($payload['amount']);
            }

            $projection->last_event_version = $event->event_version;
            $projection->last_event_at = new \DateTimeImmutable($event->occurred_at);

            $processedEvents++;
        }

        // Save projection
        $projection->save();

        // Update hot account tracking in Redis
        if ($processedEvents > 0) {
            try {
                Redis::connection('hot-accounts')
                    ->zincrby('hot_accounts', $processedEvents, $this->accountId);
            } catch (\Exception $e) {
                // Non-critical
            }
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $logger->info("Projection rebuild complete", [
            'account_id' => $this->accountId,
            'events_processed' => $processedEvents,
            'final_version' => $projection->last_event_version,
            'duration_ms' => $duration,
        ]);
    }
}
