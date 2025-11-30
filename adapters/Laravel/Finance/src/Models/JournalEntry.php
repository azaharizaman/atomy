<?php

declare(strict_types=1);

namespace Nexus\Laravel\Finance\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Finance\Domain\Contracts\JournalEntryInterface;
use Nexus\Finance\Domain\Contracts\JournalEntryLineInterface;

/**
 * Eloquent model for Journal Entries
 * 
 * @property string $id
 * @property string $entry_number
 * @property \Illuminate\Support\Carbon $date
 * @property string|null $reference
 * @property string $description
 * @property string $status
 * @property string $created_by
 * @property \Illuminate\Support\Carbon|null $posted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class JournalEntry extends Model implements JournalEntryInterface
{
    use HasUlids;

    protected $table = 'gl_journal_entries';

    protected $fillable = [
        'entry_number',
        'date',
        'reference',
        'description',
        'status',
        'created_by',
        'posted_at',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
    ];

    /**
     * Get journal entry lines
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id');
    }

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
        return DateTimeImmutable::createFromMutable($this->date->toDateTime());
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return array<JournalEntryLineInterface>
     */
    public function getLines(): array
    {
        return $this->lines->all();
    }

    public function getTotalDebit(): string
    {
        return (string) $this->lines->sum(fn (JournalEntryLine $line) => (float) $line->debit_amount);
    }

    public function getTotalCredit(): string
    {
        return (string) $this->lines->sum(fn (JournalEntryLine $line) => (float) $line->credit_amount);
    }

    public function isBalanced(): bool
    {
        return bccomp($this->getTotalDebit(), $this->getTotalCredit(), 4) === 0;
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function getCreatedBy(): string
    {
        return $this->created_by;
    }

    public function getPostedAt(): ?DateTimeImmutable
    {
        if ($this->posted_at === null) {
            return null;
        }
        return DateTimeImmutable::createFromMutable($this->posted_at->toDateTime());
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->created_at->toDateTime());
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->updated_at->toDateTime());
    }
}
