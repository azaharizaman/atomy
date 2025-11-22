<?php

declare(strict_types=1);

namespace App\Models\Finance;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Finance\Contracts\AccountInterface;
use Nexus\Finance\Enums\AccountType;

/**
 * Account Model
 * 
 * Eloquent model implementing AccountInterface for general ledger accounts.
 * 
 * @property string $id
 * @property string $code
 * @property string $name
 * @property AccountType $account_type
 * @property string $currency
 * @property string|null $parent_id
 * @property bool $is_header
 * @property bool $is_active
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class Account extends Model implements AccountInterface
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'account_type',
        'currency',
        'parent_id',
        'is_header',
        'is_active',
        'description',
    ];

    protected $casts = [
        'account_type' => AccountType::class,
        'is_header' => 'boolean',
        'is_active' => 'boolean',
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
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return $this->account_type->value;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * {@inheritDoc}
     */
    public function getParentId(): ?string
    {
        return $this->parent_id;
    }

    /**
     * {@inheritDoc}
     */
    public function isHeader(): bool
    {
        return $this->is_header;
    }

    /**
     * {@inheritDoc}
     */
    public function isActive(): bool
    {
        return $this->is_active;
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
     * Parent account relationship
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Child accounts relationship
     */
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /**
     * Journal entry lines using this account
     */
    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }
}
