<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Contracts;

/**
 * Model repository interface
 * 
 * Persistence operations for models, predictions, and usage
 */
interface ModelRepositoryInterface
{
    /**
     * Find model configuration by name
     * 
     * @param string $tenantId Tenant identifier
     * @param string $modelName Model name
     * @return array<string, mixed>|null Configuration array or null
     */
    public function findModelByName(string $tenantId, string $modelName): ?array;

    /**
     * Store model configuration
     * 
     * @param array<string, mixed> $data Model configuration
     * @return string Model ID
     */
    public function storeModelConfiguration(array $data): string;

    /**
     * Record prediction
     * 
     * @param array<string, mixed> $data Prediction data
     * @return string Prediction ID
     */
    public function recordPrediction(array $data): string;

    /**
     * Record usage metrics
     * 
     * @param string $tenantId Tenant identifier
     * @param string $modelName Model name
     * @param string $domainContext Domain context
     * @param array<string, mixed> $metrics Usage metrics
     * @return void
     */
    public function recordUsage(string $tenantId, string $modelName, string $domainContext, array $metrics): void;

    /**
     * Get A/B test configuration
     * 
     * @param string $tenantId Tenant identifier
     * @param string $modelName Model name
     * @return array<string, mixed>|null Configuration or null
     */
    public function getABTestConfiguration(string $tenantId, string $modelName): ?array;

    /**
     * Store calibration curve
     * 
     * @param string $modelName Model name
     * @param array<string, mixed> $calibrationData Calibration data
     * @return void
     */
    public function storeCalibrationCurve(string $modelName, array $calibrationData): void;

    /**
     * Get custom fine-tuned endpoint for tenant
     * 
     * @param string $tenantId Tenant identifier
     * @param string $modelName Model name
     * @return string|null Endpoint URL or null
     */
    public function getCustomEndpoint(string $tenantId, string $modelName): ?string;

    /**
     * Get current active model version
     * 
     * @param string $modelName Model name
     * @return array<string, mixed>|null Version data or null
     */
    public function getCurrentVersion(string $modelName): ?array;

    /**
     * Get version history
     * 
     * @param string $modelName Model name
     * @return array<array<string, mixed>> Array of version data
     */
    public function getVersionHistory(string $modelName): array;

    /**
     * Record adversarial test result
     * 
     * @param string $modelName Model name
     * @param array<string, mixed> $result Test result data
     * @return void
     */
    public function recordAdversarialTest(string $modelName, array $result): void;

    /**
     * Store cost recommendation
     * 
     * @param array<string, mixed> $recommendation Recommendation data
     * @return string Recommendation ID
     */
    public function storeCostRecommendation(array $recommendation): string;
}
