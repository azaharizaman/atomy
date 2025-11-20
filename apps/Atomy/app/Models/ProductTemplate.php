<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Product\Contracts\ProductTemplateInterface;

/**
 * Product Template Model
 *
 * Eloquent implementation of ProductTemplateInterface.
 */
class ProductTemplate extends Model implements ProductTemplateInterface
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'product_templates';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'category_code',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    // Relationships

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_code', 'code');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'template_id');
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

    public function getCategoryCode(): ?string
    {
        return $this->category_code;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
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
