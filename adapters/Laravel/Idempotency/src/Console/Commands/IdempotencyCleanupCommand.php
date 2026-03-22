<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Nexus\Idempotency\Domain\IdempotencyPolicy;

final class IdempotencyCleanupCommand extends Command
{
    protected $signature = 'idempotency:cleanup {--chunk=500 : Rows per delete batch}';

    protected $description = 'Delete expired Nexus idempotency records (pending TTL + optional completed TTL)';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $pendingTtl = (int) config(
            'nexus-idempotency.policy.pending_ttl_seconds',
            IdempotencyPolicy::DEFAULT_PENDING_TTL_SECONDS,
        );
        $completedTtl = config('nexus-idempotency.policy.expire_completed_after_seconds');
        $now = Carbon::now('UTC');

        $pendingCutoff = $now->copy()->subSeconds($pendingTtl)->format('Y-m-d H:i:s');

        $deleted = 0;
        $deleted += $this->deleteWhere(
            $chunk,
            static fn ($q) => $q->where('status', 'pending')->where('created_at', '<', $pendingCutoff),
        );
        $deleted += $this->deleteWhere(
            $chunk,
            static fn ($q) => $q->where('status', 'failed')->where('last_transition_at', '<', $pendingCutoff),
        );

        if (is_int($completedTtl) || (is_string($completedTtl) && $completedTtl !== '')) {
            $ttl = (int) $completedTtl;
            $completedCutoff = $now->copy()->subSeconds($ttl)->format('Y-m-d H:i:s');
            $deleted += $this->deleteWhere(
                $chunk,
                static fn ($q) => $q->where('status', 'completed')->where('last_transition_at', '<', $completedCutoff),
            );
        }

        $this->info('Deleted ' . (string) $deleted . ' idempotency row(s).');

        return self::SUCCESS;
    }

    /**
     * @param callable(\Illuminate\Database\Query\Builder): \Illuminate\Database\Query\Builder $constraints
     */
    private function deleteWhere(int $chunk, callable $constraints): int
    {
        $deleted = 0;
        while (true) {
            $query = DB::table('nexus_idempotency_records');
            $ids = $constraints($query)
                ->limit($chunk)
                ->pluck('id');
            if ($ids->isEmpty()) {
                break;
            }
            $deleted += DB::table('nexus_idempotency_records')->whereIn('id', $ids)->delete();
        }

        return $deleted;
    }
}
