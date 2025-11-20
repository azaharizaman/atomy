<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Intelligence Usage
 * 
 * Tracks API usage and costs per tenant/model/domain.
 * 
 * @property string $id
 * @property string $tenant_id
 * @property string $model_name
 * @property string $domain_context
 * @property int $tokens_used
 * @property int $api_calls
 * @property float $api_cost
 * @property string $period_month
 * @property \Carbon\Carbon $created_at
 */
class IntelligenceUsage extends Model
{
    use HasUlids;

    protected $table = 'intelligence_usage';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'model_name',
        'domain_context',
        'tokens_used',
        'api_calls',
        'api_cost',
        'period_month',
        'created_at',
    ];

    protected $casts = [
        'tokens_used' => 'integer',
        'api_calls' => 'integer',
        'api_cost' => 'float',
        'created_at' => 'datetime',
    ];
}
