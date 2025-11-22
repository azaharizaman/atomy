<?php

declare(strict_types=1);

namespace App\Models\Finance;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Finance\Contracts\JournalEntryInterface;
use Nexus\Finance\Enums\JournalEntryStatus;

/**
 * Journal Entry Model
 * 
 * Eloquent model implementing JournalEntryInterface for general ledger journal entries.
 * 
 * @property string $id
 * @property string $entry_number
 * @property \Illuminate\Support\Carbon $entry_date
 * @property string|null $reference
 * @property string $description
 * @property JournalEntryStatus $status
 * @property string $created_by
 * @property \Illuminate\Support\Carbon|null $posted_at
 * @property string|null $posted_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class JournalEntry extends Model implements JournalEntryInterface
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'entry_number',
        'entry_date',
        'reference',
        'description',
        'status',
        'created_by',
        'posted_at',
        'posted_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'status' => JournalEntryStatus::class,
        'posted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
    public function getEntryNumber(): string
    {
        return $this->entry_number;
    }

    /**
     * {@inheritDoc}
     */
    public function getDate(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->entry_date);
    }

    /**
     * {@inheritDoc}
     */
    public function getReference(): ?string
    {
        return $this->reference;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus(): string
    {
        return $this->status->value;
    }

    /**
     * {@inheritDoc}
     */
    public function getLines(): array
    {
        return $this->lines->all();
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalDebit(): string
    {
        return $this->lines->sum(fn($line) => $line->getDebitAmount()->getAmount());
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalCredit(): string
    {
        return $this->lines->sum(fn($line) => $line->getCreditAmount()->getAmount());
    }

    /**
     * {@inheritDoc}
     */
    public function isBalanced(): bool
    {
        $totalDebit = $this->getTotalDebit();
        $totalCredit = $this->getTotalCredit();
        
        return bccomp($totalDebit, $totalCredit, 4) === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function isPosted(): bool
    {
        return $this->status === JournalEntryStatus::Posted;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreatedBy(): string
    {
        return $this->created_by;
    }

    /**
     * {@inheritDoc}
     */
    public function getPostedAt(): ?DateTimeImmutable
    {
        return $this->posted_at 
            ? DateTimeImmutable::createFromMutable($this->posted_at) 
            : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->created_at);
    }

    /**
     * {@inheritDoc}
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->updated_at);
    }

    /**
     * Journal entry lines relationship
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id');
    }
}
