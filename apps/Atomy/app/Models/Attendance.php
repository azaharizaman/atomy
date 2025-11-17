<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Hrm\Contracts\AttendanceInterface;
use Nexus\Hrm\ValueObjects\AttendanceStatus;

class Attendance extends Model implements AttendanceInterface
{
    use HasUlids;

    protected $table = 'attendance';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'date',
        'clock_in_time',
        'clock_out_time',
        'break_minutes',
        'total_hours',
        'overtime_hours',
        'status',
        'clock_in_location',
        'clock_out_location',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_out_latitude',
        'clock_out_longitude',
        'remarks',
        'approved_by',
        'approved_at',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in_time' => 'datetime',
        'clock_out_time' => 'datetime',
        'total_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'clock_in_latitude' => 'decimal:8',
        'clock_in_longitude' => 'decimal:8',
        'clock_out_latitude' => 'decimal:8',
        'clock_out_longitude' => 'decimal:8',
        'approved_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
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

    public function getEmployeeId(): string
    {
        return $this->employee_id;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function getClockInTime(): ?\DateTimeInterface
    {
        return $this->clock_in_time;
    }

    public function getClockOutTime(): ?\DateTimeInterface
    {
        return $this->clock_out_time;
    }

    public function getBreakMinutes(): int
    {
        return $this->break_minutes;
    }

    public function getTotalHours(): ?float
    {
        return $this->total_hours ? (float) $this->total_hours : null;
    }

    public function getOvertimeHours(): ?float
    {
        return $this->overtime_hours ? (float) $this->overtime_hours : null;
    }

    public function getStatus(): AttendanceStatus
    {
        return AttendanceStatus::from($this->status);
    }

    public function getRemarks(): ?string
    {
        return $this->remarks;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function isClockedIn(): bool
    {
        return $this->clock_in_time !== null && $this->clock_out_time === null;
    }

    public function isClockedOut(): bool
    {
        return $this->clock_out_time !== null;
    }
}
