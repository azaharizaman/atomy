<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Intelligence Prediction
 * 
 * Stores individual prediction/evaluation results.
 * 
 * @property string $id
 * @property string|null $job_id
 * @property string $model_id
 * @property string|null $model_version
 * @property array $features_json
 * @property string $features_hash
 * @property array $result_json
 * @property float|null $raw_confidence
 * @property float|null $calibrated_confidence
 * @property array|null $feature_importance_json
 * @property bool $requires_review
 * @property bool $is_adversarial
 * @property string $status
 * @property bool|null $actual_outcome
 * @property int|null $deployment_age_hours
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class IntelligencePrediction extends Model
{
    use HasUlids;

    protected $table = 'intelligence_predictions';

    protected $fillable = [
        'job_id',
        'model_id',
        'model_version',
        'features_json',
        'features_hash',
        'result_json',
        'raw_confidence',
        'calibrated_confidence',
        'feature_importance_json',
        'requires_review',
        'is_adversarial',
        'status',
        'actual_outcome',
        'deployment_age_hours',
    ];

    protected $casts = [
        'features_json' => 'array',
        'result_json' => 'array',
        'raw_confidence' => 'float',
        'calibrated_confidence' => 'float',
        'feature_importance_json' => 'array',
        'requires_review' => 'boolean',
        'is_adversarial' => 'boolean',
        'actual_outcome' => 'boolean',
        'deployment_age_hours' => 'integer',
    ];

    /**
     * Get associated model
     */
    public function model()
    {
        return $this->belongsTo(IntelligenceModel::class, 'model_id');
    }
}
