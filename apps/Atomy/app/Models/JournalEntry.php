<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Finance\Contracts\JournalEntryInterface;

/**
 * JournalEntry Eloquent Model
 * 
 * Implements JournalEntryInterface from Nexus\Finance package.
 */
class JournalEntry extends Model implements JournalEntryInterface
{
    use HasUlids;

    protected $table = 'journal_entries';

    protected $fillable = [
        'entry_number',
        'entry_date',
        'status',
        'description',
        'reference',
        'period_id',
        'created_by',
        'posted_at',
        'posted_by',
        'reversed_at',
        'reversed_by',
        'reversal_of_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getId(): string
    {
        return $this->id;
    }

    public function getEntryNumber(): string
    {
        return $this->entry_number;
    }

    public function getDate(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->entry_date);
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getCreatedBy(): string
    {
        return $this->created_by ?? '';
    }

    public function getPeriodId(): ?string
    {
        return $this->period_id;
    }

    public function getPostedAt(): ?DateTimeImmutable
    {
        return $this->posted_at 
            ? DateTimeImmutable::createFromMutable($this->posted_at)
            : null;
    }

    public function getPostedBy(): ?string
    {
        return $this->posted_by;
    }

    public function getReversedAt(): ?DateTimeImmutable
    {
        return $this->reversed_at
            ? DateTimeImmutable::createFromMutable($this->reversed_at)
            : null;
    }

    public function getReversedBy(): ?string
    {
        return $this->reversed_by;
    }

    public function getReversalOfId(): ?string
    {
        return $this->reversal_of_id;
    }

    public function getLines(): array
    {
        return $this->lines->all();
    }

    public function getTotalDebit(): string
    {
        return (string) $this->lines->sum('debit_amount');
    }

    public function getTotalCredit(): string
    {
        return (string) $this->lines->sum('credit_amount');
    }

    public function isBalanced(): bool
    {
        return bccomp($this->getTotalDebit(), $this->getTotalCredit(), 4) === 0;
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->created_at);
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->updated_at);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_of_id');
    }

    public function reversedBy(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'reversal_of_id');
    }

    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeInPeriod($query, string $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('entry_date', [$startDate, $endDate]);
    }
}
