<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\FieldService\Contracts\ServiceContractInterface;
use Nexus\FieldService\Enums\ContractStatus;

/**
 * Service Contract Eloquent Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $contract_number
 * @property string $customer_party_id
 * @property string|null $asset_id
 * @property ContractStatus $status
 * @property \DateTimeInterface $start_date
 * @property \DateTimeInterface $end_date
 * @property string|null $response_time
 * @property int|null $maintenance_interval_days
 * @property array $covered_services
 * @property array|null $metadata
 */
class ServiceContract extends Model implements ServiceContractInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'contract_number',
        'customer_party_id',
        'asset_id',
        'status',
        'start_date',
        'end_date',
        'response_time',
        'maintenance_interval_days',
        'covered_services',
        'metadata',
    ];

    protected $casts = [
        'status' => ContractStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'maintenance_interval_days' => 'integer',
        'covered_services' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ServiceContractInterface implementation

    public function getContractNumber(): string
    {
        return $this->contract_number;
    }

    public function getCustomerPartyId(): string
    {
        return $this->customer_party_id;
    }

    public function getAssetId(): ?string
    {
        return $this->asset_id;
    }

    public function getStatus(): ContractStatus
    {
        return $this->status;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->start_date);
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->end_date);
    }

    public function getResponseTime(): ?string
    {
        return $this->response_time;
    }

    public function getMaintenanceIntervalDays(): ?int
    {
        return $this->maintenance_interval_days;
    }

    public function getCoveredServices(): array
    {
        return $this->covered_services;
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    public function isActive(): bool
    {
        $now = new \DateTimeImmutable();
        return $this->status === ContractStatus::ACTIVE
            && $this->getStartDate() <= $now
            && $this->getEndDate() >= $now;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->created_at);
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->updated_at);
    }

    // Eloquent Relationships

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'customer_party_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    // Scopes

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        $now = now();
        return $query->where('status', ContractStatus::ACTIVE)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        $threshold = now()->addDays($days);
        return $query->where('status', ContractStatus::ACTIVE)
            ->whereBetween('end_date', [now(), $threshold]);
    }

    public function scopeDueForMaintenance($query)
    {
        return $query->where('status', ContractStatus::ACTIVE)
            ->whereNotNull('maintenance_interval_days');
    }
}
