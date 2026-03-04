<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Tests\Unit\Services;

use Nexus\IntelligenceOperations\Contracts\DataDriftPortInterface;
use Nexus\IntelligenceOperations\Contracts\ModelRegistryPortInterface;
use Nexus\IntelligenceOperations\Contracts\ModelTelemetryPortInterface;
use Nexus\IntelligenceOperations\Contracts\ModelTrainingPortInterface;
use Nexus\IntelligenceOperations\Coordinators\ModelLifecycleCoordinator;
use Nexus\IntelligenceOperations\DataProviders\ModelHealthDataProvider;
use Nexus\IntelligenceOperations\Rules\ModelDeploymentRule;
use Nexus\IntelligenceOperations\Rules\ModelHealthRule;
use Nexus\IntelligenceOperations\Workflows\ModelDeploymentWorkflow;
use Nexus\IntelligenceOperations\Workflows\ModelHealthWorkflow;
use Nexus\IntelligenceOperations\Workflows\RetrainingWorkflow;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ModelLifecycleManagerTest extends TestCase
{
    public function test_deploy_model_and_compute_health(): void
    {
        $telemetry = new class implements ModelTelemetryPortInterface {
            public function increment(string $metric, float $value = 1.0, array $tags = []): void {}
            public function timing(string $metric, float $milliseconds, array $tags = []): void {}
            public function gauge(string $metric, float $value, array $tags = []): void {}
            public function modelMetrics(string $modelId): array
            {
                return ['accuracy' => 0.93, 'latency_ms' => 125.0, 'drift_score' => 0.02];
            }
        };

        $coordinator = new ModelLifecycleCoordinator(
            new ModelDeploymentWorkflow(
                new ModelDeploymentRule(),
                new class implements ModelRegistryPortInterface {
                    public function registerVersion(string $modelId, string $version, array $configuration): bool { return true; }
                    public function getCurrentVersion(string $modelId): ?array { return ['version' => 'v2']; }
                    public function getVersionHistory(string $modelId): array { return [['version' => 'v1'], ['version' => 'v2']]; }
                },
                $telemetry
            ),
            new RetrainingWorkflow(
                new class implements DataDriftPortInterface {
                    public function calculateDriftScore(string $modelId): float { return 0.31; }
                },
                new class implements ModelTrainingPortInterface {
                    public function queueRetraining(string $modelId, array $context): string { return 'job-123'; }
                },
                $telemetry
            ),
            new ModelHealthWorkflow(
                new ModelHealthDataProvider(
                    $telemetry,
                    new class implements DataDriftPortInterface {
                        public function calculateDriftScore(string $modelId): float { return 0.02; }
                    }
                ),
                new ModelHealthRule()
            ),
            new NullLogger()
        );

        self::assertTrue($coordinator->deployModel('fraud-detector', 'v2', ['threshold' => 0.8]));

        $health = $coordinator->getModelHealth('fraud-detector');
        self::assertSame('healthy', $health['status']);
        self::assertSame(0.93, $health['accuracy']);
    }

    public function test_trigger_retraining_returns_job_id(): void
    {
        $telemetry = new class implements ModelTelemetryPortInterface {
            public function increment(string $metric, float $value = 1.0, array $tags = []): void {}
            public function timing(string $metric, float $milliseconds, array $tags = []): void {}
            public function gauge(string $metric, float $value, array $tags = []): void {}
            public function modelMetrics(string $modelId): array { return ['accuracy' => 0.8, 'latency_ms' => 260.0]; }
        };

        $coordinator = new ModelLifecycleCoordinator(
            new ModelDeploymentWorkflow(
                new ModelDeploymentRule(),
                new class implements ModelRegistryPortInterface {
                    public function registerVersion(string $modelId, string $version, array $configuration): bool { return true; }
                    public function getCurrentVersion(string $modelId): ?array { return null; }
                    public function getVersionHistory(string $modelId): array { return []; }
                },
                $telemetry
            ),
            new RetrainingWorkflow(
                new class implements DataDriftPortInterface {
                    public function calculateDriftScore(string $modelId): float { return 0.4; }
                },
                new class implements ModelTrainingPortInterface {
                    public function queueRetraining(string $modelId, array $context): string
                    {
                        return 'retrain-789';
                    }
                },
                $telemetry
            ),
            new ModelHealthWorkflow(
                new ModelHealthDataProvider($telemetry, new class implements DataDriftPortInterface {
                    public function calculateDriftScore(string $modelId): float { return 0.4; }
                }),
                new ModelHealthRule()
            ),
            new NullLogger()
        );

        self::assertSame('retrain-789', $coordinator->triggerRetraining('forecasting-model'));
    }
}
