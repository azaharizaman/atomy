<?php

declare(strict_types=1);

namespace Nexus\Laravel\ApprovalOperations\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $subject_type
 * @property string $workflow_definition_id
 * @property string $policy_id
 * @property string $policy_version
 * @property int $template_version
 */
final class OperationalApprovalTemplate extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'operational_approval_templates';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'tenant_id',
        'subject_type',
        'workflow_definition_id',
        'policy_id',
        'policy_version',
        'template_version',
    ];
}
