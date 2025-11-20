<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\IntelligenceModel;
use App\Models\IntelligencePrediction;
use App\Models\IntelligenceUsage;
use Nexus\Intelligence\Contracts\ModelRepositoryInterface;

/**
 * Database implementation of model repository
 */
final class DbIntelligenceRepository implements ModelRepositoryInterface
{
    public function findModelByName(string $tenantId, string $modelName): ?array
    {
        $model = IntelligenceModel::where('tenant_id', $tenantId)
            ->where('name', $modelName)
            ->where('is_active', true)
            ->first();

        if ($model === null) {
            return null;
        }

        return [
            'id' => $model->id,
            'name' => $model->name,
            'type' => $model->type,
            'provider' => $model->provider,
            'endpoint_url' => $model->endpoint_url,
            'custom_endpoint_url' => $model->custom_endpoint_url,
            'current_version' => $model->current_version,
            'config_json' => $model->config_json,
            'expected_feature_version' => $model->expected_feature_version,
            'baseline_confidence' => $model->baseline_confidence,
            'drift_threshold' => $model->drift_threshold,
            'ab_test_enabled' => $model->ab_test_enabled,
            'ab_test_model_b' => $model->ab_test_model_b,
            'ab_test_weight' => $model->ab_test_weight,
            'calibration_enabled' => $model->calibration_enabled,
        ];
    }

    public function storeModelConfiguration(array $data): string
    {
        $model = IntelligenceModel::create($data);
        return $model->id;
    }

    public function recordPrediction(array $data): string
    {
        $prediction = IntelligencePrediction::create($data);
        return $prediction->id;
    }

    public function recordUsage(string $tenantId, string $modelName, string $domainContext, array $metrics): void
    {
        $now = new \DateTimeImmutable();
        
        IntelligenceUsage::create([
            'tenant_id' => $tenantId,
            'model_name' => $modelName,
            'domain_context' => $domainContext,
            'tokens_used' => $metrics['tokens_used'] ?? 0,
            'api_calls' => $metrics['api_calls'] ?? 1,
            'api_cost' => $metrics['api_cost'] ?? 0.0,
            'period_month' => $now->format('Y-m'),
            'created_at' => $now,
        ]);
    }

    public function getABTestConfiguration(string $tenantId, string $modelName): ?array
    {
        $model = IntelligenceModel::where('tenant_id', $tenantId)
            ->where('name', $modelName)
            ->where('ab_test_enabled', true)
            ->first();

        if ($model === null) {
            return null;
        }

        return [
            'model_b' => $model->ab_test_model_b,
            'weight' => $model->ab_test_weight,
        ];
    }

    public function storeCalibrationCurve(string $modelName, array $calibrationData): void
    {
        // TODO: Implement calibration storage in Phase 2
    }

    public function getCustomEndpoint(string $tenantId, string $modelName): ?string
    {
        $model = IntelligenceModel::where('tenant_id', $tenantId)
            ->where('name', $modelName)
            ->whereNotNull('custom_endpoint_url')
            ->first();

        return $model?->custom_endpoint_url;
    }

    public function getCurrentVersion(string $modelName): ?array
    {
        // TODO: Implement version management in Phase 2
        return null;
    }

    public function getVersionHistory(string $modelName): array
    {
        // TODO: Implement version history in Phase 2
        return [];
    }

    public function recordAdversarialTest(string $modelName, array $result): void
    {
        // TODO: Implement adversarial testing in Phase 2
    }

    public function storeCostRecommendation(array $recommendation): string
    {
        // TODO: Implement cost optimization in Phase 2
        return '';
    }
}
