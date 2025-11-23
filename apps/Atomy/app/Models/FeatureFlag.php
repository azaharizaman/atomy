<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Nexus\FeatureFlags\Enums\FlagStrategy;

/**
 * Eloquent model for feature flags table.
 *
 * Implements FlagDefinitionInterface for compatibility with Nexus\FeatureFlags package.
 *
 * @property string $id ULID primary key
 * @property string|null $tenant_id Tenant scoping (null = global)
 * @property string $name Flag name (max 100 chars)
 * @property bool $enabled Flag enabled state
 * @property FlagStrategy $strategy Evaluation strategy
 * @property mixed $value Strategy-specific value (percentage, list, etc.)
 * @property FlagOverride|null $override Kill switch override
 * @property array|null $metadata Custom metadata
 * @property string $checksum SHA-256 checksum for cache validation
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class FeatureFlag extends Model implements FlagDefinitionInterface
{
    use HasUlids;

    /**
     * The table associated with the model.
     */
    protected $table = 'feature_flags';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'enabled',
        'strategy',
        'value',
        'override',
        'metadata',
        'checksum',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
        'strategy' => FlagStrategy::class,
        'value' => 'json',
        'override' => FlagOverride::class,
        'metadata' => 'json',
    ];

    // ========================================
    // Eloquent Relationships
    // ========================================

    /**
     * Get the tenant this flag belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ========================================
    // FlagDefinitionInterface Implementation
    // ========================================

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getStrategy(): FlagStrategy
    {
        return $this->strategy;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getOverride(): ?FlagOverride
    {
        return $this->override;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    // ========================================
    // Model Events
    // ========================================

    /**
     * Boot the model and register event listeners.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Calculate checksum before saving
        static::saving(function (self $flag) {
            $flag->checksum = $flag->calculateChecksum();
        });
    }

    /**
     * Calculate SHA-256 checksum for cache validation.
     *
     * Matches FlagDefinition::calculateChecksum() logic.
     */
    private function calculateChecksum(): string
    {
        $data = [
            'enabled' => $this->enabled,
            'strategy' => $this->strategy->value,
            'value' => $this->value,
            'override' => $this->override?->value,
        ];

        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }
}
