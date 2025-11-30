<?php

declare(strict_types=1);

namespace Nexus\Laravel\Finance\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Finance\Domain\Contracts\JournalEntryLineInterface;
use Nexus\Finance\Domain\ValueObjects\Money;

/**
 * Eloquent model for Journal Entry Lines
 * 
 * @property string $id
 * @property string $journal_entry_id
 * @property string $account_id
 * @property string $debit_amount
 * @property string $credit_amount
 * @property string $currency
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class JournalEntryLine extends Model implements JournalEntryLineInterface
{
    use HasUlids;

    protected $table = 'gl_journal_entry_lines';

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit_amount',
        'credit_amount',
        'currency',
        'description',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:4',
        'credit_amount' => 'decimal:4',
    ];

    /**
     * Get the journal entry this line belongs to
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    /**
     * Get the account this line posts to
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getJournalEntryId(): string
    {
        return $this->journal_entry_id;
    }

    public function getAccountId(): string
    {
        return $this->account_id;
    }

    public function getDebitAmount(): Money
    {
        return Money::of($this->debit_amount ?? '0', $this->currency);
    }

    public function getCreditAmount(): Money
    {
        return Money::of($this->credit_amount ?? '0', $this->currency);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isDebit(): bool
    {
        return bccomp($this->debit_amount ?? '0', '0', 4) > 0;
    }

    public function isCredit(): bool
    {
        return bccomp($this->credit_amount ?? '0', '0', 4) > 0;
    }

    public function getNetAmount(): Money
    {
        $debit = Money::of($this->debit_amount ?? '0', $this->currency);
        $credit = Money::of($this->credit_amount ?? '0', $this->currency);
        
        return $debit->subtract($credit);
    }
}
