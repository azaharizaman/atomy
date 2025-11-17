<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Hrm\Contracts\TrainingInterface;
use Nexus\Hrm\ValueObjects\TrainingStatus;

class Training extends Model implements TrainingInterface
{
    use HasUlids, SoftDeletes;

    protected $table = 'trainings';

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'category',
        'provider',
        'start_date',
        'end_date',
        'duration_hours',
        'location',
        'max_participants',
        'cost',
        'currency',
        'status',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'duration_hours' => 'decimal:2',
        'cost' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function getDurationHours(): ?float
    {
        return $this->duration_hours ? (float) $this->duration_hours : null;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->max_participants;
    }

    public function getCost(): ?float
    {
        return $this->cost ? (float) $this->cost : null;
    }

    public function getStatus(): TrainingStatus
    {
        return TrainingStatus::from($this->status);
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function isActive(): bool
    {
        return $this->getStatus() === TrainingStatus::ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->getStatus() === TrainingStatus::COMPLETED;
    }

    public function hasAvailableSlots(): bool
    {
        if ($this->max_participants === null) {
            return true;
        }
        
        return $this->enrollments()->whereIn('status', ['enrolled', 'in_progress'])->count() < $this->max_participants;
    }
}
