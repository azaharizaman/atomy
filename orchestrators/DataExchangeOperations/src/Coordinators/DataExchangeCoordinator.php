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
        $tenantId = trim($tenantId);
        if ($tenantId === '') {
            throw new \InvalidArgumentException('tenantId is required for onboarding.');
        }

        $taskId = self::generateTaskId('onboard');

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
                'tenant_hash' => hash('sha256', $tenantId),
                'source_path_hash' => hash('sha256', $sourcePath),
                'error_class' => $e::class,
                'error_code' => $e->getCode(),
            ]);
            throw $e;
        }

        return $taskId;
    }

    public function getTaskStatus(string $tenantId, string $taskId): array
    {
        $tenantId = trim($tenantId);
        if ($tenantId === '') {
            throw new \InvalidArgumentException('tenantId is required for task status lookups.');
        }

        $status = $this->taskStore->findForTenant($tenantId, $taskId);

        if ($status === null) {
            return [
                'tenant_id' => $tenantId,
                'task_id' => $taskId,
                'status' => 'not_found',
            ];
        }

        return $status->toArray();
    }

    public function offboard(array $query, string $format, string $destination, array $recipients = []): string
    {
        $rawSnakeTenantId = $query['tenant_id'] ?? null;
        $rawCamelTenantId = $query['tenantId'] ?? null;

        if ($rawSnakeTenantId !== null && !is_string($rawSnakeTenantId)) {
            throw new \InvalidArgumentException('tenant_id is required for offboarding.');
        }

        if ($rawCamelTenantId !== null && !is_string($rawCamelTenantId)) {
            throw new \InvalidArgumentException('tenant_id is required for offboarding.');
        }

        $snakeTenantId = is_string($rawSnakeTenantId) ? trim($rawSnakeTenantId) : null;
        $camelTenantId = is_string($rawCamelTenantId) ? trim($rawCamelTenantId) : null;

        if ($snakeTenantId !== null && $camelTenantId !== null && $snakeTenantId !== $camelTenantId) {
            throw new \InvalidArgumentException('Conflicting tenant identifiers provided: tenant_id and tenantId must match.');
        }

        $tenantId = $snakeTenantId ?? $camelTenantId;
        if ($tenantId === null || $tenantId === '') {
            throw new \InvalidArgumentException('tenant_id is required for offboarding.');
        }

        $query['tenant_id'] = $tenantId;
        unset($query['tenantId']);

        $taskId = self::generateTaskId('offboard');

        try {
            $this->offboardingWorkflow->run(new DataOffboardingRequest(
                taskId: $taskId,
                tenantId: $tenantId,
                query: $query,
                format: $format,
                destination: $destination,
                recipients: $recipients,
            ));
        } catch (\Throwable $e) {
            $this->logger->error('Data offboarding failed.', [
                'task_id' => $taskId,
                'destination_hash' => hash('sha256', $destination),
                'format' => $format,
                'error_class' => $e::class,
                'error_code' => $e->getCode(),
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
