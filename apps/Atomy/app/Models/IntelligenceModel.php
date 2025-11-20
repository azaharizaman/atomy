<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Intelligence Model
 * 
 * Stores AI model configurations per tenant.
 * 
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $type
 * @property string $provider
 * @property string|null $endpoint_url
 * @property string|null $custom_endpoint_url
 * @property string|null $current_version
 * @property array $config_json
 * @property string $expected_feature_version
 * @property float|null $baseline_confidence
 * @property float $drift_threshold
 * @property bool $ab_test_enabled
 * @property string|null $ab_test_model_b
 * @property float $ab_test_weight
 * @property bool $calibration_enabled
 * @property bool $adversarial_testing_enabled
 * @property bool $cost_optimization_enabled
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class IntelligenceModel extends Model
{
    use HasUlids;

    protected $table = 'intelligence_models';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'provider',
        'endpoint_url',
        'custom_endpoint_url',
        'current_version',
        'config_json',
        'expected_feature_version',
        'baseline_confidence',
        'drift_threshold',
        'ab_test_enabled',
        'ab_test_model_b',
        'ab_test_weight',
        'calibration_enabled',
        'adversarial_testing_enabled',
        'cost_optimization_enabled',
        'is_active',
    ];

    protected $casts = [
        'config_json' => 'array',
        'baseline_confidence' => 'float',
        'drift_threshold' => 'float',
        'ab_test_enabled' => 'boolean',
        'ab_test_weight' => 'float',
        'calibration_enabled' => 'boolean',
        'adversarial_testing_enabled' => 'boolean',
        'cost_optimization_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get model predictions
     */
    public function predictions()
    {
        return $this->hasMany(IntelligencePrediction::class, 'model_id');
    }
}
