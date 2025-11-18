<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Finance\Contracts\JournalEntryLineInterface;
use Nexus\Finance\ValueObjects\Money;

/**
 * JournalEntryLine Eloquent Model
 * 
 * Implements JournalEntryLineInterface from Nexus\Finance package.
 */
class JournalEntryLine extends Model implements JournalEntryLineInterface
{
    use HasUlids;

    protected $table = 'journal_entry_lines';

    protected $fillable = [
        'journal_entry_id',
        'line_number',
        'account_id',
        'debit_amount',
        'credit_amount',
        'description',
        'currency',
    ];

    protected $casts = [
        'line_number' => 'integer',
        'debit_amount' => 'decimal:4',
        'credit_amount' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
        return Money::of((string)$this->debit_amount, $this->currency ?? 'MYR');
    }

    public function getCreditAmount(): Money
    {
        return Money::of((string)$this->credit_amount, $this->currency ?? 'MYR');
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isDebit(): bool
    {
        return bccomp((string)$this->debit_amount, '0', 4) > 0;
    }

    public function isCredit(): bool
    {
        return bccomp((string)$this->credit_amount, '0', 4) > 0;
    }

    public function getNetAmount(): Money
    {
        $currency = $this->currency ?? 'MYR';
        $debit = Money::of((string)$this->debit_amount, $currency);
        $credit = Money::of((string)$this->credit_amount, $currency);
        
        return $debit->subtract($credit);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->created_at);
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->updated_at);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

