<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Product\Contracts\AttributeSetInterface;

/**
 * Attribute Model
 *
 * Eloquent implementation of AttributeSetInterface.
 */
class Attribute extends Model implements AttributeSetInterface
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'attributes';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'values',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'values' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // Interface implementation

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getValues(): array
    {
        return $this->values ?? [];
    }

    public function getSortOrder(): int
    {
        return $this->sort_order;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->created_at);
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->updated_at);
    }
}
