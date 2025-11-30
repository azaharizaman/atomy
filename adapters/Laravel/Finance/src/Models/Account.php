<?php

declare(strict_types=1);

namespace Nexus\Laravel\Finance\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Finance\Domain\Contracts\AccountInterface;

/**
 * Eloquent model for GL Accounts
 * 
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property string $currency
 * @property string|null $parent_id
 * @property bool $is_header
 * @property bool $is_active
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Account extends Model implements AccountInterface
{
    use HasUlids;

    protected $table = 'gl_accounts';

    protected $fillable = [
        'code',
        'name',
        'type',
        'currency',
        'parent_id',
        'is_header',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_header' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent account
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get child accounts
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get journal entry lines for this account
     */
    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getParentId(): ?string
    {
        return $this->parent_id;
    }

    public function isHeader(): bool
    {
        return $this->is_header;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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
