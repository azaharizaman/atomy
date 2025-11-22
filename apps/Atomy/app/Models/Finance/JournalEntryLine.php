<?php

declare(strict_types=1);

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Finance\Contracts\JournalEntryLineInterface;
use Nexus\Finance\ValueObjects\Money;

/**
 * Journal Entry Line Model
 * 
 * Eloquent model implementing JournalEntryLineInterface for journal entry line items.
 * 
 * @property string $id
 * @property string $journal_entry_id
 * @property string $account_id
 * @property string $debit_amount
 * @property string $credit_amount
 * @property string $debit_currency
 * @property string $credit_currency
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class JournalEntryLine extends Model implements JournalEntryLineInterface
{
    use HasFactory;

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit_amount',
        'credit_amount',
        'debit_currency',
        'credit_currency',
        'description',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:4',
        'credit_amount' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Validate debit/credit mutual exclusivity before saving
        static::saving(function (self $line) {
            $debitIsZero = bccomp($line->debit_amount, '0', 4) === 0;
            $creditIsZero = bccomp($line->credit_amount, '0', 4) === 0;

            // Either debit OR credit must be non-zero (not both, not neither)
            if ($debitIsZero && $creditIsZero) {
                throw new \InvalidArgumentException('Journal entry line must have either debit or credit amount (not both zero)');
            }

            if (!$debitIsZero && !$creditIsZero) {
                throw new \InvalidArgumentException('Journal entry line cannot have both debit and credit amounts');
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getJournalEntryId(): string
    {
        return $this->journal_entry_id;
    }

    /**
     * {@inheritDoc}
     */
    public function getAccountId(): string
    {
        return $this->account_id;
    }

    /**
     * {@inheritDoc}
     */
    public function getDebitAmount(): Money
    {
        return Money::of($this->debit_amount, $this->debit_currency);
    }

    /**
     * {@inheritDoc}
     */
    public function getCreditAmount(): Money
    {
        return Money::of($this->credit_amount, $this->credit_currency);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * {@inheritDoc}
     */
    public function isDebit(): bool
    {
        return bccomp($this->debit_amount, '0', 4) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function isCredit(): bool
    {
        return bccomp($this->credit_amount, '0', 4) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getNetAmount(): Money
    {
        $debit = $this->getDebitAmount();
        $credit = $this->getCreditAmount();
        
        return $debit->subtract($credit);
    }

    /**
     * Journal entry relationship
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    /**
     * Account relationship
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
