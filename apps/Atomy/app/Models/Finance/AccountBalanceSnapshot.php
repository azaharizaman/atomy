<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use App\Models\Tenant;
use App\Models\Finance\Account;

/**
 * Account Balance Snapshot (Event Replay Optimization)
 * 
 * Dynamic snapshots created when event count exceeds threshold.
 * Used to speed up projection rebuilds by skipping old events.
 * 
 * Auto-adjusts threshold based on account activity:
 * - Hot accounts (high access): Lower threshold (50 events)
 * - Normal accounts: Default threshold (100 events)
 * - Cold accounts (low access): Higher threshold (500 events)
 * 
 * @property string $id
 * @property string $tenant_id
 * @property string $account_id
 * @property int $event_version
 * @property \DateTimeImmutable $snapshot_at
 * @property array $snapshot_data
 * @property int $threshold
 * @property int $events_since_snapshot
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 */
final class AccountBalanceSnapshot extends Model
{
    use HasUlids;
    
    protected $table = 'account_balance_snapshots';
    
    protected $fillable = [
        'tenant_id',
        'account_id',
        'event_version',
        'snapshot_at',
        'snapshot_data',
        'threshold',
        'events_since_snapshot',
    ];
    
    protected $casts = [
        'event_version' => 'integer',
        'snapshot_at' => 'immutable_datetime',
        'snapshot_data' => 'array',
        'threshold' => 'integer',
        'events_since_snapshot' => 'integer',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
    
    /**
     * Get the tenant this snapshot belongs to
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    /**
     * Get the account this snapshot tracks
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
    
    /**
     * Check if new snapshot should be created
     */
    public function shouldCreateSnapshot(): bool
    {
        return $this->events_since_snapshot >= $this->threshold;
    }
    
    /**
     * Adjust threshold based on account activity
     * 
     * @param int $accessCount Account access count from projection
     */
    public function adjustThreshold(int $accessCount): void
    {
        // Hot accounts (>1000 accesses): snapshot every 50 events
        if ($accessCount > 1000) {
            $this->threshold = 50;
        }
        // Normal accounts (100-1000 accesses): default 100 events
        elseif ($accessCount >= 100) {
            $this->threshold = 100;
        }
        // Cold accounts (<100 accesses): snapshot every 500 events
        else {
            $this->threshold = 500;
        }
    }
    
    /**
     * Create snapshot from projection state
     */
    public static function createFromProjection(
        AccountBalanceProjection $projection,
        int $eventVersion
    ): self {
        return self::create([
            'tenant_id' => $projection->tenant_id,
            'account_id' => $projection->account_id,
            'event_version' => $eventVersion,
            'snapshot_at' => now(),
            'snapshot_data' => [
                'debit_balance' => $projection->debit_balance,
                'credit_balance' => $projection->credit_balance,
                'current_balance' => $projection->current_balance,
            ],
            'threshold' => 100, // Default, will adjust on next access
            'events_since_snapshot' => 0,
        ]);
    }
}
