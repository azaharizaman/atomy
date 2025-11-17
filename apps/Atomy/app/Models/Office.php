<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Backoffice\Contracts\OfficeInterface;

class Office extends Model implements OfficeInterface
{
    use HasUlids;

    protected $fillable = [
        'company_id', 'code', 'name', 'type', 'status', 'parent_office_id',
        'address_line1', 'address_line2', 'city', 'state', 'country', 'postal_code',
        'phone', 'email', 'fax', 'timezone', 'operating_hours',
        'staff_capacity', 'floor_area', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'staff_capacity' => 'integer',
        'floor_area' => 'float',
    ];

    public function getId(): string { return $this->id; }
    public function getCompanyId(): string { return $this->company_id; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getType(): string { return $this->type; }
    public function getStatus(): string { return $this->status; }
    public function getParentOfficeId(): ?string { return $this->parent_office_id; }
    public function getAddressLine1(): string { return $this->address_line1; }
    public function getAddressLine2(): ?string { return $this->address_line2; }
    public function getCity(): string { return $this->city; }
    public function getState(): ?string { return $this->state; }
    public function getCountry(): string { return $this->country; }
    public function getPostalCode(): string { return $this->postal_code; }
    public function getPhone(): ?string { return $this->phone; }
    public function getEmail(): ?string { return $this->email; }
    public function getFax(): ?string { return $this->fax; }
    public function getTimezone(): ?string { return $this->timezone; }
    public function getOperatingHours(): ?string { return $this->operating_hours; }
    public function getStaffCapacity(): ?int { return $this->staff_capacity; }
    public function getFloorArea(): ?float { return $this->floor_area; }
    public function getMetadata(): array { return $this->metadata ?? []; }
    public function getCreatedAt(): \DateTimeInterface { return $this->created_at; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updated_at; }
    public function isHeadOffice(): bool { return $this->type === 'head_office'; }
    public function isActive(): bool { return in_array($this->status, ['active', 'temporary']); }
}
