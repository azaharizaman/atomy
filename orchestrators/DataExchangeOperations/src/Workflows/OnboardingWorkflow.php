<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Workflows;

use DateTimeImmutable;
use Nexus\DataExchangeOperations\Contracts\DataExchangeTaskStoreInterface;
use Nexus\DataExchangeOperations\Contracts\DataImportPortInterface;
use Nexus\DataExchangeOperations\Contracts\StoragePortInterface;
use Nexus\DataExchangeOperations\DTOs\DataExchangeTaskStatus;
use Nexus\DataExchangeOperations\DTOs\DataOnboardingRequest;
use Nexus\DataExchangeOperations\DTOs\DataOnboardingResult;
use Nexus\DataExchangeOperations\Rules\OnboardingPreflightRule;

final readonly class OnboardingWorkflow
{
    public function __construct(
        private OnboardingPreflightRule $preflightRule,
        private DataImportPortInterface $importPort,
        private StoragePortInterface $storage,
        private DataExchangeTaskStoreInterface $taskStore,
    ) {}

    public function run(DataOnboardingRequest $request): DataOnboardingResult
    {
        $this->taskStore->save(new DataExchangeTaskStatus(
            taskId: $request->taskId,
            type: 'onboarding',
            status: 'validating',
            updatedAt: new DateTimeImmutable(),
            payload: ['source_path' => $request->sourcePath, 'tenant_id' => $request->tenantId],
        ));

        try {
            $this->preflightRule->assert($request);

            $this->taskStore->save(new DataExchangeTaskStatus(
                taskId: $request->taskId,
                type: 'onboarding',
                status: 'importing',
                updatedAt: new DateTimeImmutable(),
            ));

            $imported = $this->importPort->import($request);
            if (!is_array($imported)) {
                throw new \UnexpectedValueException('Import adapter returned invalid payload; expected array.');
            }

            $recordsProcessed = $imported['records_processed'] ?? null;
            $recordsFailed = $imported['records_failed'] ?? null;
            $warnings = $imported['warnings'] ?? null;
            $details = $imported['details'] ?? null;

            if (!is_int($recordsProcessed) || !is_int($recordsFailed) || !is_array($warnings) || !is_array($details)) {
                throw new \UnexpectedValueException(
                    'Import adapter payload must contain records_processed(int), records_failed(int), warnings(array), and details(array).'
                );
            }

            if ((bool) ($request->options['cleanup'] ?? true)) {
                $this->storage->delete($request->sourcePath);
            }

            $result = new DataOnboardingResult(
                taskId: $request->taskId,
                recordsProcessed: $recordsProcessed,
                recordsFailed: $recordsFailed,
                warnings: $warnings,
                details: $details,
            );

            $this->taskStore->save(new DataExchangeTaskStatus(
                taskId: $request->taskId,
                type: 'onboarding',
                status: 'completed',
                updatedAt: new DateTimeImmutable(),
                payload: $result->toArray(),
            ));

            return $result;
        } catch (\Throwable $e) {
            $this->taskStore->save(new DataExchangeTaskStatus(
                taskId: $request->taskId,
                type: 'onboarding',
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
