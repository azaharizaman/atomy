<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Coordinators;

use Nexus\DataExchangeOperations\Contracts\DataExchangeTaskStoreInterface;
use Nexus\DataExchangeOperations\Contracts\DataOffboardingCoordinatorInterface;
use Nexus\DataExchangeOperations\Contracts\DataOnboardingCoordinatorInterface;
use Nexus\DataExchangeOperations\DTOs\DataOffboardingRequest;
use Nexus\DataExchangeOperations\DTOs\DataOnboardingRequest;
use Nexus\DataExchangeOperations\Workflows\OffboardingWorkflow;
use Nexus\DataExchangeOperations\Workflows\OnboardingWorkflow;
use Psr\Log\LoggerInterface;

final readonly class DataExchangeCoordinator implements DataOnboardingCoordinatorInterface, DataOffboardingCoordinatorInterface
{
    public function __construct(
        private OnboardingWorkflow $onboardingWorkflow,
        private OffboardingWorkflow $offboardingWorkflow,
        private DataExchangeTaskStoreInterface $taskStore,
        private LoggerInterface $logger,
    ) {}

    public function onboard(string $sourcePath, string $tenantId, array $options = []): string
    {
        $taskId = $options['task_id'] ?? self::generateTaskId('onboard');

        try {
            $this->onboardingWorkflow->run(new DataOnboardingRequest(
                taskId: $taskId,
                tenantId: $tenantId,
                sourcePath: $sourcePath,
                options: $options,
            ));
        } catch (\Throwable $e) {
            $this->logger->error('Data onboarding failed.', [
                'task_id' => $taskId,
                'tenant_id' => $tenantId,
                'source_path' => $sourcePath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $taskId;
    }

    public function getTaskStatus(string $taskId): array
    {
        $status = $this->taskStore->find($taskId);

        if ($status === null) {
            return [
                'task_id' => $taskId,
                'status' => 'not_found',
            ];
        }

        return $status->toArray();
    }

    public function offboard(array $query, string $format, string $destination, array $recipients = []): string
    {
        $taskId = self::generateTaskId('offboard');

        try {
            $this->offboardingWorkflow->run(new DataOffboardingRequest(
                taskId: $taskId,
                query: $query,
                format: $format,
                destination: $destination,
                recipients: $recipients,
            ));
        } catch (\Throwable $e) {
            $this->logger->error('Data offboarding failed.', [
                'task_id' => $taskId,
                'destination' => $destination,
                'format' => $format,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $taskId;
    }

    private static function generateTaskId(string $prefix): string
    {
        return sprintf('%s_%s', $prefix, bin2hex(random_bytes(10)));
    }
}
