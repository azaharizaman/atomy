<?php

declare(strict_types=1);

namespace App\Models\Backoffice;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAssignment extends Model
{
    use HasFactory;
    use HasUlids;

    protected $table = 'backoffice_staff_assignments';

    protected $fillable = [
        'staff_id',
        'department_id',
        'job_title',
        'is_primary',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
