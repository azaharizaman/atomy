<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use App\Models\Tenant;
use App\Models\Finance\Account;

/**
 * Account Balance Projection (Read Model)
 * 
 * Materialized view of account balances rebuilt from event_streams.
 * Updated asynchronously via UpdateAccountBalanceProjection listener.
 * 
 * Features:
 * - Optimistic locking via updated_at timestamp
 * - Event versioning to prevent duplicate processing
 * - Hot account tracking for Redis sorted set caching
 * - LRU eviction support via access_count and last_accessed_at
 * 
 * @property string $id
 * @property string $tenant_id
 * @property string $account_id
 * @property string $debit_balance
 * @property string $credit_balance
 * @property string $current_balance
 * @property int $last_event_version
 * @property \DateTimeImmutable|null $last_event_at
 * @property int $access_count
 * @property \DateTimeImmutable|null $last_accessed_at
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 */
final class AccountBalanceProjection extends Model
{
    use HasUlids;
    
    protected $table = 'account_balance_projections';
    
    protected $fillable = [
        'tenant_id',
        'account_id',
        'debit_balance',
        'credit_balance',
        'current_balance',
        'last_event_version',
        'last_event_at',
        'access_count',
        'last_accessed_at',
    ];
    
    protected $casts = [
        'debit_balance' => 'decimal:4',
        'credit_balance' => 'decimal:4',
        'current_balance' => 'decimal:4',
        'last_event_version' => 'integer',
        'last_event_at' => 'immutable_datetime',
        'access_count' => 'integer',
        'last_accessed_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
    
    /**
     * Get the tenant this projection belongs to
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    /**
     * Get the account this projection tracks
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
    
    /**
     * Increment debit balance and recalculate current balance
     */
    public function addDebit(string $amount): void
    {
        $this->debit_balance = bcadd($this->debit_balance, $amount, 4);
        $this->recalculateBalance();
    }
    
    /**
     * Increment credit balance and recalculate current balance
     */
    public function addCredit(string $amount): void
    {
        $this->credit_balance = bcadd($this->credit_balance, $amount, 4);
        $this->recalculateBalance();
    }
    
    /**
     * Recalculate current_balance = debit_balance - credit_balance
     */
    private function recalculateBalance(): void
    {
        $this->current_balance = bcsub($this->debit_balance, $this->credit_balance, 4);
    }
    
    /**
     * Mark as accessed (for hot account tracking)
     */
    public function markAccessed(): void
    {
        $this->access_count++;
        $this->last_accessed_at = now();
    }
}
