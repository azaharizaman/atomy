<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Tests\Unit\Services;

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

final class ReportingCoordinatorTest extends TestCase
{
    public function test_run_pipeline_includes_forecast_and_stores_file(): void
    {
        $tracker = new class {
            /** @var array<int, string> */
            public array $storedPaths = [];
            /** @var array<string, mixed>|null */
            public ?array $lastExportedPayload = null;
            public ?string $forecastModelId = null;
            /** @var array<int, string> */
            public array $tempFiles = [];
        };

        $coordinator = new ReportingCoordinator(
            new ReportingPipelineWorkflow(
                new ReportingPipelineRule(),
                new class implements ReportDataQueryPortInterface {
                    public function query(string $reportTemplateId, array $parameters): array
                    {
                        return ['revenue' => 1000.0, 'cost' => 400.0];
                    }
                },
                new class($tracker) implements ForecastPortInterface {
                    public function __construct(private object $tracker) {}
                    public function forecast(string $modelId, array $context, int $maxAttempts, int $pollIntervalMs): array
                    {
                        $this->tracker->forecastModelId = $modelId;

                        return [
                            'status' => 'success',
                            'data' => ['value' => 1200.0],
                            'confidence' => 0.91,
                            'model_version' => 'v2',
                            'error' => null,
                        ];
                    }
                },
                new class($tracker) implements ReportExportPortInterface {
                    public function __construct(private object $tracker) {}
                    public function export(array $reportData, string $format): array
                    {
                        $file = tempnam(sys_get_temp_dir(), 'insight_report_');
                        file_put_contents($file, json_encode($reportData));
                        $this->tracker->lastExportedPayload = $reportData;
                        $this->tracker->tempFiles[] = $file;

                        return [
                            'file_path' => $file,
                            'size_bytes' => filesize($file) ?: 0,
                            'metadata' => ['format' => $format],
                        ];
                    }
                },
                new class($tracker) implements InsightStoragePortInterface {
                    public function __construct(private object $tracker) {}
                    public function put(string $path, mixed $content): void
                    {
                        $this->tracker->storedPaths[] = $path;
                    }
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

        $path = $coordinator->runPipeline('sales-summary', [
            'include_forecast' => true,
            'forecast_model_id' => 'sales-model',
        ], [
            'format' => 'pdf',
            'recipients' => ['ops@example.com'],
        ]);

        self::assertStringContainsString('reports/', $path);
        self::assertNotEmpty($tracker->storedPaths);
        self::assertSame('sales-model', $tracker->forecastModelId);
        self::assertArrayHasKey('forecast', $tracker->lastExportedPayload ?? []);
        self::assertNotEmpty($tracker->lastExportedPayload['forecast'] ?? null);

        foreach ($tracker->tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function test_capture_snapshot_returns_snapshot_path(): void
    {
        $tracker = new class {
            /** @var array<int, string> */
            public array $tempFiles = [];
        };

        $coordinator = new ReportingCoordinator(
            new ReportingPipelineWorkflow(
                new ReportingPipelineRule(),
                new class implements ReportDataQueryPortInterface {
                    public function query(string $reportTemplateId, array $parameters): array { return []; }
                },
                new class implements ForecastPortInterface {
                    public function forecast(string $modelId, array $context, int $maxAttempts, int $pollIntervalMs): array
                    {
                        return ['status' => 'success', 'data' => [], 'confidence' => 1.0, 'model_version' => 'v1', 'error' => null];
                    }
                },
                new class($tracker) implements ReportExportPortInterface {
                    public function __construct(private object $tracker) {}
                    public function export(array $reportData, string $format): array
                    {
                        $file = tempnam(sys_get_temp_dir(), 'insight_snapshot_');
                        file_put_contents($file, 'x');
                        $this->tracker->tempFiles[] = $file;

                        return ['file_path' => $file, 'size_bytes' => 1, 'metadata' => []];
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
                            queryHistory: [['widgets' => []]],
                        );
                    }
                },
                new class implements InsightStoragePortInterface {
                    public function put(string $path, mixed $content): void {}
                }
            ),
            new NullLogger()
        );

        $snapshotPath = $coordinator->captureSnapshot('dashboard-a', 'tenant-a');

        self::assertStringContainsString('snapshots/tenant-a/dashboard-a/', $snapshotPath);

        foreach ($tracker->tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
