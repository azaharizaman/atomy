<?php

declare(strict_types=1);

namespace Nexus\Laravel\ApprovalOperations\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $template_id
 * @property string $workflow_instance_id
 * @property string $subject_type
 * @property string $subject_id
 * @property string $status
 */
final class OperationalApprovalInstance extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'operational_approval_instances';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'tenant_id',
        'template_id',
        'workflow_instance_id',
        'subject_type',
        'subject_id',
        'status',
    ];
}
