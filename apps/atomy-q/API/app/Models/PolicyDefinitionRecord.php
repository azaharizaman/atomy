<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $policy_id
 * @property string $policy_version
 * @property string $payload
 */
final class PolicyDefinitionRecord extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'policy_definitions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'tenant_id',
        'policy_id',
        'policy_version',
        'payload',
    ];

    public function setTenantIdAttribute(string $value): void
    {
        $this->attributes['tenant_id'] = mb_strtolower(trim($value));
    }
}
