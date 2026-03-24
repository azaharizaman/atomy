<?php

declare(strict_types=1);

namespace Nexus\Laravel\ApprovalOperations\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $instance_id
 * @property string $author_principal_id
 * @property string $body
 * @property string|null $attachment_storage_key
 */
final class OperationalApprovalComment extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'operational_approval_comments';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'tenant_id',
        'instance_id',
        'author_principal_id',
        'body',
        'attachment_storage_key',
    ];
}
