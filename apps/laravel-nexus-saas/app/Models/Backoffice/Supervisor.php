<?php

declare(strict_types=1);

namespace App\Models\Backoffice;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supervisor extends Model
{
    use HasFactory;
    use HasUlids;

    protected $table = 'backoffice_supervisors';

    protected $fillable = [
        'staff_id',
        'supervisor_id',
        'type',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'supervisor_id');
    }
}
