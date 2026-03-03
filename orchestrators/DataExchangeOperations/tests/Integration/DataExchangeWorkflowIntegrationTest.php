<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Tests\Integration;

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

final class DataExchangeWorkflowIntegrationTest extends TestCase
{
    public function test_full_exchange_flow_produces_completed_tasks(): void
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
                        return ['records_processed' => 42, 'records_failed' => 1, 'warnings' => ['row 11 invalid'], 'details' => ['file' => $request->sourcePath]];
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
                        return ['source_path' => '/tmp/export-data.csv', 'format' => 'csv', 'size_bytes' => 999, 'metadata' => ['rows' => 42]];
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

        $onboardingTask = $coordinator->onboard('/tmp/in.csv', 'tenant-x', ['task_id' => 'onboard-123']);
        $offboardingTask = $coordinator->offboard(['tenant_id' => 'tenant-x'], 'csv', 'exports/tenant-x', ['ops@example.com']);

        self::assertSame('completed', $coordinator->getTaskStatus($onboardingTask)['status']);
        self::assertSame('completed', $coordinator->getTaskStatus($offboardingTask)['status']);
    }
}
