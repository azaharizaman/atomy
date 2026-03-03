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
            payload: ['destination' => $request->destination, 'format' => $request->format],
        ));

        try {
            $this->preflightRule->assert($request);

            $this->taskStore->save(new DataExchangeTaskStatus(
                taskId: $request->taskId,
                type: 'offboarding',
                status: 'exporting',
                updatedAt: new DateTimeImmutable(),
            ));

            $exported = $this->exportPort->export($request);

            $destinationPath = rtrim($request->destination, '/') . '/' . basename($exported['source_path']);
            $stored = $this->storage->store($destinationPath, $exported['source_path']);

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
                payload: $result->toArray(),
            ));

            return $result;
        } catch (\Throwable $e) {
            $this->taskStore->save(new DataExchangeTaskStatus(
                taskId: $request->taskId,
                type: 'offboarding',
                status: 'failed',
                updatedAt: new DateTimeImmutable(),
                payload: [
                    'error' => $e->getMessage(),
                    'exception' => $e::class,
                ],
            ));

            throw $e;
        }
    }
}
