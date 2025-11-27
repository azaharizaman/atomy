<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Nexus\FeatureFlags\Enums\FlagStrategy;

/**
 * Feature Flag model implementing FlagDefinitionInterface.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string|null $description
 * @property bool $enabled
 * @property string $strategy
 * @property array|null $value
 * @property string|null $override
 * @property array $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $created_by
 * @property string|null $updated_by
 */
class FeatureFlag extends Model implements FlagDefinitionInterface
{
    use HasUlids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'feature_flags';

    /**
     * The primary key type.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'description',
        'enabled',
        'strategy',
        'value',
        'override',
        'metadata',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
        'value' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'enabled' => false,
        'strategy' => 'system_wide',
        'metadata' => '[]',
    ];

    // ==========================================
    // FlagDefinitionInterface Implementation
    // ==========================================

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled(): bool
    {
        return (bool) $this->enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getStrategy(): FlagStrategy
    {
        return FlagStrategy::from($this->strategy);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getOverride(): ?FlagOverride
    {
        if ($this->override === null) {
            return null;
        }

        return FlagOverride::from($this->override);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getChecksum(): string
    {
        return md5(json_encode([
            'name' => $this->name,
            'enabled' => $this->enabled,
            'strategy' => $this->strategy,
            'value' => $this->value,
            'override' => $this->override,
            'metadata' => $this->metadata,
        ]) ?: '');
    }

    // ==========================================
    // Additional Methods
    // ==========================================

    /**
     * Get the tenant ID for this flag.
     */
    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    /**
     * Get the flag description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Get user flag overrides for this flag.
     */
    public function userOverrides(): HasMany
    {
        return $this->hasMany(UserFlagOverride::class, 'flag_name', 'name')
            ->where('tenant_id', $this->tenant_id);
    }

    /**
     * Set the enabled state.
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Set the strategy.
     */
    public function setStrategy(FlagStrategy $strategy): self
    {
        $this->strategy = $strategy->value;
        return $this;
    }

    /**
     * Set the value.
     */
    public function setValue(mixed $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Set the override.
     */
    public function setOverride(?FlagOverride $override): self
    {
        $this->override = $override?->value;
        return $this;
    }

    /**
     * Set the metadata.
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Set the description.
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Toggle the enabled state.
     */
    public function toggle(): self
    {
        $this->enabled = !$this->enabled;
        return $this;
    }

    /**
     * Scope query to a specific tenant.
     */
    public function scopeTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope query to enabled flags only.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope query to a specific strategy.
     */
    public function scopeByStrategy($query, FlagStrategy $strategy)
    {
        return $query->where('strategy', $strategy->value);
    }

    /**
     * Convert to array for API responses.
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'description' => $this->description,
            'enabled' => $this->enabled,
            'strategy' => $this->strategy,
            'value' => $this->value,
            'override' => $this->override,
            'metadata' => $this->metadata,
            'checksum' => $this->getChecksum(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
        ];
    }
}
