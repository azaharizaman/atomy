<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MfaEnrollment extends Model
{
    use HasUlids;

    protected $table = 'mfa_enrollments';
    protected $fillable = ['user_id', 'method', 'secret', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
