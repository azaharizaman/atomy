<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\FieldService\Contracts\ChecklistItemInterface;
use Nexus\FieldService\Enums\ChecklistItemType;

/**
 * Checklist Template Eloquent Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string|null $description
 * @property array $items
 * @property bool $is_active
 */
class ChecklistTemplate extends Model
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'items',
        'is_active',
    ];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Business Logic

    /**
     * Get checklist items as ChecklistItemInterface array
     *
     * @return array<ChecklistItemInterface>
     */
    public function getChecklistItems(): array
    {
        return array_map(
            fn(array $item) => new ChecklistItem($item),
            $this->items
        );
    }

    // Eloquent Relationships

    public function responses(): HasMany
    {
        return $this->hasMany(ChecklistResponse::class);
    }

    // Scopes

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

/**
 * Internal ChecklistItem implementation for array items
 */
class ChecklistItem implements ChecklistItemInterface
{
    public function __construct(
        private readonly array $data
    ) {}

    public function getLabel(): string
    {
        return $this->data['label'];
    }

    public function getType(): ChecklistItemType
    {
        return ChecklistItemType::from($this->data['type']);
    }

    public function isRequired(): bool
    {
        return $this->data['required'] ?? false;
    }

    public function getOptions(): ?array
    {
        return $this->data['options'] ?? null;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
