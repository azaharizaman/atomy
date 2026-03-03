<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Tests\Integration;

use Nexus\InsightOperations\Contracts\DashboardSnapshotPortInterface;
use Nexus\InsightOperations\Contracts\ForecastPortInterface;
use Nexus\InsightOperations\Contracts\InsightNotificationPortInterface;
use Nexus\InsightOperations\Contracts\InsightStoragePortInterface;
use Nexus\InsightOperations\Contracts\ReportDataQueryPortInterface;
use Nexus\InsightOperations\Contracts\ReportExportPortInterface;
use Nexus\InsightOperations\Coordinators\ReportingCoordinator;
use Nexus\InsightOperations\DataProviders\PipelineContextDataProvider;
use Nexus\InsightOperations\DTOs\DashboardSnapshotDto;
use Nexus\InsightOperations\Rules\DashboardSnapshotRule;
use Nexus\InsightOperations\Rules\ReportingPipelineRule;
use Nexus\InsightOperations\Workflows\DashboardSnapshotWorkflow;
use Nexus\InsightOperations\Workflows\ReportingPipelineWorkflow;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ReportingPipelineIntegrationTest extends TestCase
{
    public function test_pipeline_and_snapshot_workflows_complete(): void
    {
        $tracker = new class {
            /** @var array<int, string> */
            public array $tempFiles = [];
        };

        $coordinator = new ReportingCoordinator(
            new ReportingPipelineWorkflow(
                new ReportingPipelineRule(),
                new class implements ReportDataQueryPortInterface {
                    public function query(string $reportTemplateId, array $parameters): array { return ['kpi' => 100]; }
                },
                new class implements ForecastPortInterface {
                    public function forecast(string $modelId, array $context, int $maxAttempts, int $pollIntervalMs): array
                    {
                        return ['status' => 'success', 'data' => ['value' => 120], 'confidence' => 0.88, 'model_version' => 'v1', 'error' => null];
                    }
                },
                new class($tracker) implements ReportExportPortInterface {
                    public function __construct(private object $tracker) {}
                    public function export(array $reportData, string $format): array
                    {
                        $f = tempnam(sys_get_temp_dir(), 'report_');
                        file_put_contents($f, json_encode($reportData));
                        $this->tracker->tempFiles[] = $f;

                        return ['file_path' => $f, 'size_bytes' => filesize($f) ?: 0, 'metadata' => ['format' => $format]];
                    }
                },
                new class implements InsightStoragePortInterface {
                    public function put(string $path, mixed $content): void {}
                },
                new class implements InsightNotificationPortInterface {
                    public function notify(array $recipients, string $template, array $payload): void {}
                },
                new PipelineContextDataProvider()
            ),
            new DashboardSnapshotWorkflow(
                new DashboardSnapshotRule(),
                new class implements DashboardSnapshotPortInterface {
                    public function snapshot(string $dashboardId, string $tenantId): DashboardSnapshotDto
                    {
                        return new DashboardSnapshotDto(
                            tenantId: $tenantId,
                            dashboardId: $dashboardId,
                            capturedAt: gmdate(DATE_ATOM),
                            queryHistory: [],
                        );
                    }
                },
                new class implements InsightStoragePortInterface {
                    public function put(string $path, mixed $content): void {}
                }
            ),
            new NullLogger()
        );

        try {
            $reportPath = $coordinator->runPipeline('ops-report', ['include_forecast' => true], ['format' => 'pdf']);
            $snapshotPath = $coordinator->captureSnapshot('ops-dashboard', 'tenant-x');

            self::assertStringContainsString('reports/', $reportPath);
            self::assertStringContainsString('snapshots/tenant-x/ops-dashboard/', $snapshotPath);
        } finally {
            foreach ($tracker->tempFiles as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}
