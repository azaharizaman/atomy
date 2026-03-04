<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Workflows;

use DateTimeImmutable;
use Nexus\DataExchangeOperations\Contracts\DataExchangeTaskStoreInterface;
use Nexus\DataExchangeOperations\Contracts\DataExportPortInterface;
use Nexus\DataExchangeOperations\Contracts\NotificationPortInterface;
use Nexus\DataExchangeOperations\Contracts\StoragePortInterface;
use Nexus\DataExchangeOperations\DTOs\DataExchangeTaskStatus;
use Nexus\DataExchangeOperations\DTOs\DataOffboardingRequest;
use Nexus\DataExchangeOperations\DTOs\DataOffboardingResult;
use Nexus\DataExchangeOperations\Rules\OffboardingPreflightRule;

final readonly class OffboardingWorkflow
{
    public function __construct(
        private OffboardingPreflightRule $preflightRule,
        private DataExportPortInterface $exportPort,
        private StoragePortInterface $storage,
        private NotificationPortInterface $notifier,
        private DataExchangeTaskStoreInterface $taskStore,
    ) {}

    public function run(DataOffboardingRequest $request): DataOffboardingResult
    {
        $this->taskStore->save(new DataExchangeTaskStatus(
            taskId: $request->taskId,
            type: 'offboarding',
            status: 'validating',
            updatedAt: new DateTimeImmutable(),
            payload: ['tenant_id' => $request->tenantId, 'destination' => $request->destination, 'format' => $request->format],
        ));

        try {
            $this->preflightRule->assert($request);

            $this->taskStore->save(new DataExchangeTaskStatus(
                taskId: $request->taskId,
                type: 'offboarding',
                status: 'exporting',
                updatedAt: new DateTimeImmutable(),
                payload: ['tenant_id' => $request->tenantId],
            ));

            $exported = $this->exportPort->export($request);
            if (
                !is_array($exported) ||
                !isset($exported['source_path'], $exported['metadata']) ||
                !is_string($exported['source_path']) ||
                trim($exported['source_path']) === '' ||
                !is_array($exported['metadata'])
            ) {
                throw new \DomainException('Invalid export payload: required keys are source_path(string) and metadata(array).');
            }

            $destinationPath = rtrim($request->destination, '/') . '/' . basename($exported['source_path']);
            $stored = $this->storage->store($destinationPath, $exported['source_path']);
            if (
                !is_array($stored) ||
                !isset($stored['uri'], $stored['size_bytes']) ||
                !is_string($stored['uri']) ||
                trim($stored['uri']) === '' ||
                !is_int($stored['size_bytes'])
            ) {
                throw new \DomainException('Invalid storage payload: required keys are uri(string) and size_bytes(int).');
            }

            $result = new DataOffboardingResult(
                taskId: $request->taskId,
                sourcePath: $exported['source_path'],
                storedUri: $stored['uri'],
                sizeBytes: $stored['size_bytes'],
                exportMetadata: $exported['metadata'],
            );

            if ($request->recipients !== []) {
                $this->notifier->notify($request->recipients, 'data_offboarding_ready', [
                    'task_id' => $request->taskId,
                    'uri' => $stored['uri'],
                    'format' => $request->format,
                    'size_bytes' => $stored['size_bytes'],
                ]);
            }

            $this->taskStore->save(new DataExchangeTaskStatus(
                taskId: $request->taskId,
                type: 'offboarding',
                status: 'completed',
                updatedAt: new DateTimeImmutable(),
                payload: array_merge($result->toArray(), ['tenant_id' => $request->tenantId]),
            ));

            return $result;
        } catch (\Throwable $e) {
            $this->taskStore->save(new DataExchangeTaskStatus(
                taskId: $request->taskId,
                type: 'offboarding',
                status: 'failed',
                updatedAt: new DateTimeImmutable(),
                payload: [
                    'tenant_id' => $request->tenantId,
                    'error' => $e->getMessage(),
                    'exception' => $e::class,
                ],
            ));

            throw $e;
        }
    }
}
