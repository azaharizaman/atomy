<?php

declare(strict_types=1);

namespace Nexus\IntelligenceOperations\Tests\Integration;

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

final class ModelLifecycleIntegrationTest extends TestCase
{
    public function test_lifecycle_flow_deploy_retrain_health(): void
    {
        $telemetry = new class implements ModelTelemetryPortInterface {
            public function increment(string $metric, float $value = 1.0, array $tags = []): void {}
            public function timing(string $metric, float $milliseconds, array $tags = []): void {}
            public function gauge(string $metric, float $value, array $tags = []): void {}
            public function modelMetrics(string $modelId): array { return ['accuracy' => 0.82, 'latency_ms' => 200.0, 'drift_score' => 0.11]; }
        };

        $coordinator = new ModelLifecycleCoordinator(
            new ModelDeploymentWorkflow(
                new ModelDeploymentRule(),
                new class implements ModelRegistryPortInterface {
                    public function registerVersion(string $modelId, string $version, array $configuration): bool { return true; }
                    public function getCurrentVersion(string $modelId): ?array { return ['version' => 'v3']; }
                    public function getVersionHistory(string $modelId): array { return []; }
                },
                $telemetry
            ),
            new RetrainingWorkflow(
                new class implements DataDriftPortInterface {
                    public function calculateDriftScore(string $modelId): float { return 0.11; }
                },
                new class implements ModelTrainingPortInterface {
                    public function queueRetraining(string $modelId, array $context): string { return 'job-int-456'; }
                },
                $telemetry
            ),
            new ModelHealthWorkflow(
                new ModelHealthDataProvider(
                    $telemetry,
                    new class implements DataDriftPortInterface {
                        public function calculateDriftScore(string $modelId): float { return 0.11; }
                    }
                ),
                new ModelHealthRule()
            ),
            new NullLogger()
        );

        self::assertTrue($coordinator->deployModel('risk-score', 'v3', []));
        self::assertSame('job-int-456', $coordinator->triggerRetraining('risk-score'));
        self::assertSame('degraded', $coordinator->getModelHealth('risk-score')['status']);
    }
}
