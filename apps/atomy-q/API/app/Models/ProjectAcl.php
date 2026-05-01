<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant-scoped project access row evaluated by ProjectAclService.
 *
 * Role strings are interpreted through the service hierarchy, not by this model,
 * so unknown roles fail closed during authorization checks.
 */
class ProjectAcl extends Model
{
    use HasUlids;

    protected $table = 'project_acl';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'tenant_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
