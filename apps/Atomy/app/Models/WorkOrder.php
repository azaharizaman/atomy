<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\FieldService\Contracts\WorkOrderInterface;
use Nexus\FieldService\Enums\WorkOrderStatus;
use Nexus\FieldService\Enums\WorkOrderPriority;
use Nexus\FieldService\Enums\ServiceType;
use Nexus\FieldService\ValueObjects\WorkOrderNumber;
use Nexus\FieldService\ValueObjects\LaborHours;

/**
 * Work Order Eloquent Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $number
 * @property string $customer_party_id
 * @property string|null $service_location_id
 * @property string|null $asset_id
 * @property string|null $service_contract_id
 * @property string|null $assigned_technician_id
 * @property WorkOrderStatus $status
 * @property WorkOrderPriority $priority
 * @property ServiceType $service_type
 * @property string $description
 * @property \DateTimeInterface|null $scheduled_start
 * @property \DateTimeInterface|null $scheduled_end
 * @property \DateTimeInterface|null $actual_start
 * @property \DateTimeInterface|null $actual_end
 * @property \DateTimeInterface|null $sla_deadline
 * @property string|null $technician_notes
 * @property float|null $labor_hours
 * @property float|null $labor_cost
 * @property string $labor_currency
 * @property array|null $metadata
 */
class WorkOrder extends Model implements WorkOrderInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'number',
        'customer_party_id',
        'service_location_id',
        'asset_id',
        'service_contract_id',
        'assigned_technician_id',
        'status',
        'priority',
        'service_type',
        'description',
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
        'sla_deadline',
        'technician_notes',
        'labor_hours',
        'labor_cost',
        'labor_currency',
        'metadata',
    ];

    protected $casts = [
        'status' => WorkOrderStatus::class,
        'priority' => WorkOrderPriority::class,
        'service_type' => ServiceType::class,
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'sla_deadline' => 'datetime',
        'labor_hours' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // WorkOrderInterface implementation

    public function getNumber(): WorkOrderNumber
    {
        return WorkOrderNumber::fromString($this->number);
    }

    public function getCustomerPartyId(): string
    {
        return $this->customer_party_id;
    }

    public function getServiceLocationId(): ?string
    {
        return $this->service_location_id;
    }

    public function getAssetId(): ?string
    {
        return $this->asset_id;
    }

    public function getServiceContractId(): ?string
    {
        return $this->service_contract_id;
    }

    public function getAssignedTechnicianId(): ?string
    {
        return $this->assigned_technician_id;
    }

    public function getStatus(): WorkOrderStatus
    {
        return $this->status;
    }

    public function getPriority(): WorkOrderPriority
    {
        return $this->priority;
    }

    public function getServiceType(): ServiceType
    {
        return $this->service_type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getScheduledStart(): ?\DateTimeImmutable
    {
        return $this->scheduled_start ? \DateTimeImmutable::createFromMutable($this->scheduled_start) : null;
    }

    public function getScheduledEnd(): ?\DateTimeImmutable
    {
        return $this->scheduled_end ? \DateTimeImmutable::createFromMutable($this->scheduled_end) : null;
    }

    public function getActualStart(): ?\DateTimeImmutable
    {
        return $this->actual_start ? \DateTimeImmutable::createFromMutable($this->actual_start) : null;
    }

    public function getActualEnd(): ?\DateTimeImmutable
    {
        return $this->actual_end ? \DateTimeImmutable::createFromMutable($this->actual_end) : null;
    }

    public function getSlaDeadline(): ?\DateTimeImmutable
    {
        return $this->sla_deadline ? \DateTimeImmutable::createFromMutable($this->sla_deadline) : null;
    }

    public function getTechnicianNotes(): ?string
    {
        return $this->technician_notes;
    }

    public function getLaborHours(): ?LaborHours
    {
        if ($this->labor_hours === null) {
            return null;
        }

        return LaborHours::create(
            (float) $this->labor_hours,
            $this->labor_cost ? (float) $this->labor_cost / (float) $this->labor_hours : null,
            $this->labor_currency
        );
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
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

    public function serviceLocation(): BelongsTo
    {
        return $this->belongsTo(PostalAddress::class, 'service_location_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function serviceContract(): BelongsTo
    {
        return $this->belongsTo(ServiceContract::class, 'service_contract_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_technician_id');
    }

    public function checklistResponse(): HasOne
    {
        return $this->hasOne(ChecklistResponse::class);
    }

    public function partsConsumption(): HasMany
    {
        return $this->hasMany(PartsConsumption::class);
    }

    public function signature(): HasOne
    {
        return $this->hasOne(CustomerSignature::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(WorkOrderPhoto::class);
    }

    public function gpsLog(): HasMany
    {
        return $this->hasMany(GpsTrackingLog::class);
    }

    // Scopes

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByStatus($query, WorkOrderStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            WorkOrderStatus::NEW,
            WorkOrderStatus::SCHEDULED,
            WorkOrderStatus::IN_PROGRESS,
            WorkOrderStatus::COMPLETED,
        ]);
    }

    public function scopeApproachingSla($query, \DateTimeInterface $threshold)
    {
        return $query->whereNotNull('sla_deadline')
            ->where('sla_deadline', '<=', $threshold)
            ->whereNotIn('status', [WorkOrderStatus::VERIFIED, WorkOrderStatus::CANCELLED]);
    }
}
