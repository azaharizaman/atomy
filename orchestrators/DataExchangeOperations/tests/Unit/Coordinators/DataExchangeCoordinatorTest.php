<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Tests\Unit\Coordinators;

use Nexus\DataExchangeOperations\Contracts\DataExportPortInterface;
use Nexus\DataExchangeOperations\Contracts\DataImportPortInterface;
use Nexus\DataExchangeOperations\Contracts\NotificationPortInterface;
use Nexus\DataExchangeOperations\Contracts\StoragePortInterface;
use Nexus\DataExchangeOperations\Coordinators\DataExchangeCoordinator;
use Nexus\DataExchangeOperations\DataProviders\InMemoryDataExchangeTaskStore;
use Nexus\DataExchangeOperations\Rules\OffboardingPreflightRule;
use Nexus\DataExchangeOperations\Rules\OnboardingPreflightRule;
use Nexus\DataExchangeOperations\Workflows\OffboardingWorkflow;
use Nexus\DataExchangeOperations\Workflows\OnboardingWorkflow;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class DataExchangeCoordinatorTest extends TestCase
{
    public function test_onboard_updates_task_status_to_completed(): void
    {
        $taskStore = new InMemoryDataExchangeTaskStore();
        $storage = new class implements StoragePortInterface {
            public function store(string $destinationPath, string $sourcePath): array { return ['uri' => $destinationPath, 'size_bytes' => 12]; }
            public function delete(string $path): void {}
            public function exists(string $path): bool { return true; }
        };

        $coordinator = new DataExchangeCoordinator(
            new OnboardingWorkflow(
                new OnboardingPreflightRule($storage),
                new class implements DataImportPortInterface {
                    public function import(\Nexus\DataExchangeOperations\DTOs\DataOnboardingRequest $request): array
                    {
                        return ['records_processed' => 10, 'records_failed' => 0, 'warnings' => [], 'details' => ['tenant' => $request->tenantId]];
                    }
                },
                $storage,
                $taskStore
            ),
            new OffboardingWorkflow(
                new OffboardingPreflightRule(),
                new class implements DataExportPortInterface {
                    public function export(\Nexus\DataExchangeOperations\DTOs\DataOffboardingRequest $request): array
                    {
                        return ['source_path' => '/tmp/fake.csv', 'format' => 'csv', 'size_bytes' => 20, 'metadata' => []];
                    }
                },
                $storage,
                new class implements NotificationPortInterface {
                    public function notify(array $recipients, string $template, array $context): void {}
                },
                $taskStore
            ),
            $taskStore,
            new NullLogger()
        );

        $taskId = $coordinator->onboard('/tmp/source.csv', 'tenant_a');

        self::assertStringStartsWith('onboard_', $taskId);
        self::assertSame('completed', $coordinator->getTaskStatus('tenant_a', $taskId)['status']);
    }

    public function test_offboard_uses_destination_in_storage_path(): void
    {
        $taskStore = new InMemoryDataExchangeTaskStore();
        $tracker = new class {
            public ?string $capturedPath = null;
        };

        $storage = new class($tracker) implements StoragePortInterface {
            public function __construct(private object $tracker) {}
            public function store(string $destinationPath, string $sourcePath): array
            {
                $this->tracker->capturedPath = $destinationPath;

                return ['uri' => $destinationPath, 'size_bytes' => 128];
            }
            public function delete(string $path): void {}
            public function exists(string $path): bool { return true; }
        };

        $coordinator = new DataExchangeCoordinator(
            new OnboardingWorkflow(
                new OnboardingPreflightRule($storage),
                new class implements DataImportPortInterface {
                    public function import(\Nexus\DataExchangeOperations\DTOs\DataOnboardingRequest $request): array
                    {
                        return ['records_processed' => 1, 'records_failed' => 0, 'warnings' => [], 'details' => []];
                    }
                },
                $storage,
                $taskStore
            ),
            new OffboardingWorkflow(
                new OffboardingPreflightRule(),
                new class implements DataExportPortInterface {
                    public function export(\Nexus\DataExchangeOperations\DTOs\DataOffboardingRequest $request): array
                    {
                        return ['source_path' => '/tmp/export-file.csv', 'format' => 'csv', 'size_bytes' => 50, 'metadata' => []];
                    }
                },
                $storage,
                new class implements NotificationPortInterface {
                    public function notify(array $recipients, string $template, array $context): void {}
                },
                $taskStore
            ),
            $taskStore,
            new NullLogger()
        );

        $coordinator->offboard(['tenant_id' => 't1'], 'csv', 'exports/archive', []);

        self::assertSame('exports/archive/export-file.csv', $tracker->capturedPath);
    }

    public function test_offboard_throws_for_missing_tenant_id(): void
    {
        $taskStore = new InMemoryDataExchangeTaskStore();
        $storage = new class implements StoragePortInterface {
            public function store(string $destinationPath, string $sourcePath): array { return ['uri' => $destinationPath, 'size_bytes' => 128]; }
            public function delete(string $path): void {}
            public function exists(string $path): bool { return true; }
        };

        $coordinator = new DataExchangeCoordinator(
            new OnboardingWorkflow(
                new OnboardingPreflightRule($storage),
                new class implements DataImportPortInterface {
                    public function import(\Nexus\DataExchangeOperations\DTOs\DataOnboardingRequest $request): array
                    {
                        return ['records_processed' => 1, 'records_failed' => 0, 'warnings' => [], 'details' => []];
                    }
                },
                $storage,
                $taskStore
            ),
            new OffboardingWorkflow(
                new OffboardingPreflightRule(),
                new class implements DataExportPortInterface {
                    public function export(\Nexus\DataExchangeOperations\DTOs\DataOffboardingRequest $request): array
                    {
                        return ['source_path' => '/tmp/export-file.csv', 'format' => 'csv', 'size_bytes' => 50, 'metadata' => []];
                    }
                },
                $storage,
                new class implements NotificationPortInterface {
                    public function notify(array $recipients, string $template, array $context): void {}
                },
                $taskStore
            ),
            $taskStore,
            new NullLogger()
        );

        $this->expectException(\InvalidArgumentException::class);
        $coordinator->offboard([], 'csv', 'exports/archive', []);
    }

    public function test_offboard_throws_for_conflicting_tenant_keys(): void
    {
        $taskStore = new InMemoryDataExchangeTaskStore();
        $storage = new class implements StoragePortInterface {
            public function store(string $destinationPath, string $sourcePath): array { return ['uri' => $destinationPath, 'size_bytes' => 128]; }
            public function delete(string $path): void {}
            public function exists(string $path): bool { return true; }
        };

        $coordinator = new DataExchangeCoordinator(
            new OnboardingWorkflow(
                new OnboardingPreflightRule($storage),
                new class implements DataImportPortInterface {
                    public function import(\Nexus\DataExchangeOperations\DTOs\DataOnboardingRequest $request): array
                    {
                        return ['records_processed' => 1, 'records_failed' => 0, 'warnings' => [], 'details' => []];
                    }
                },
                $storage,
                $taskStore
            ),
            new OffboardingWorkflow(
                new OffboardingPreflightRule(),
                new class implements DataExportPortInterface {
                    public function export(\Nexus\DataExchangeOperations\DTOs\DataOffboardingRequest $request): array
                    {
                        return ['source_path' => '/tmp/export-file.csv', 'format' => 'csv', 'size_bytes' => 50, 'metadata' => []];
                    }
                },
                $storage,
                new class implements NotificationPortInterface {
                    public function notify(array $recipients, string $template, array $context): void {}
                },
                $taskStore
            ),
            $taskStore,
            new NullLogger()
        );

        $this->expectException(\InvalidArgumentException::class);
        $coordinator->offboard([
            'tenant_id' => 'tenant-a',
            'tenantId' => 'tenant-b',
        ], 'csv', 'exports/archive', []);
    }
}
