<?php

declare(strict_types=1);

namespace Nexus\Laravel\ApprovalOperations\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $operational_approval_instance_id
 * @property string $workflow_definition_id
 * @property string $subject_type
 * @property string $subject_id
 * @property string $current_state
 * @property string|null $last_actor_principal_id
 * @property string|null $last_comment
 */
final class OperationalApprovalWorkflow extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'operational_approval_workflows';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'tenant_id',
        'operational_approval_instance_id',
        'workflow_definition_id',
        'subject_type',
        'subject_id',
        'current_state',
        'last_actor_principal_id',
        'last_comment',
    ];
}
