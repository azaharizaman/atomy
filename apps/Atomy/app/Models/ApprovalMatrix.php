<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * ApprovalMatrix Model
 *
 * Stores threshold-based routing rules for workflows
 */
class ApprovalMatrix extends Model
{
    use HasUlids;

    protected $table = 'approval_matrices';

    protected $fillable = [
        'name',
        'workflow_definition_id',
        'rules',
        'is_active',
    ];

    protected $casts = [
        'rules' => 'array',
        'is_active' => 'boolean',
    ];
}
