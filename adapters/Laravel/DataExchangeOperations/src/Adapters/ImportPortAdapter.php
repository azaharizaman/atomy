<?php

declare(strict_types=1);

namespace Nexus\Laravel\DataExchangeOperations\Adapters;

use Nexus\DataExchangeOperations\Contracts\DataImportPortInterface;
use Nexus\DataExchangeOperations\DTOs\DataOnboardingRequest;
use Nexus\Import\Contracts\ImportHandlerInterface;
use Nexus\Import\Contracts\TransactionManagerInterface;
use Nexus\Import\Services\ImportManager;
use Nexus\Import\ValueObjects\ImportFormat;
use Nexus\Import\ValueObjects\ImportMode;
use Nexus\Import\ValueObjects\ImportStrategy;
use Psr\Log\LoggerInterface;

final readonly class ImportPortAdapter implements DataImportPortInterface
{
    public function __construct(
        private ImportManager $importManager,
        private ?TransactionManagerInterface $transactionManager,
        private LoggerInterface $logger,
    ) {}

    public function import(DataOnboardingRequest $request): array
    {
        $handler = $request->options['handler'] ?? null;
        if (!$handler instanceof ImportHandlerInterface) {
            throw new \InvalidArgumentException('Onboarding options must provide a valid ImportHandlerInterface as "handler".');
        }

        $format = $this->resolveImportFormat((string) ($request->options['format'] ?? 'csv'));
        $mode = $this->resolveImportMode((string) ($request->options['mode'] ?? 'upsert'));
        $strategy = $this->resolveImportStrategy((string) ($request->options['strategy'] ?? 'batch'));

        $mappings = $request->options['mappings'] ?? [];
        $validationRules = $request->options['validation_rules'] ?? [];

        $this->logger->info('Executing import adapter.', [
            'task_id' => $request->taskId,
            'tenant_id' => $request->tenantId,
            'source_path' => $request->sourcePath,
            'format' => $format->value,
            'mode' => $mode->value,
            'strategy' => $strategy->value,
        ]);

        $result = $this->importManager->import(
            filePath: $request->sourcePath,
            format: $format,
            handler: $handler,
            mappings: $mappings,
            mode: $mode,
            strategy: $strategy,
            transactionManager: $this->transactionManager,
            validationRules: $validationRules,
        );

        $warnings = [];
        foreach ($result->errors as $error) {
            $warnings[] = $error->message;
        }

        return [
            'records_processed' => $result->successCount,
            'records_failed' => $result->failedCount + $result->skippedCount,
            'warnings' => $warnings,
            'details' => [
                'success_count' => $result->successCount,
                'failed_count' => $result->failedCount,
                'skipped_count' => $result->skippedCount,
                'error_count' => $result->getErrorCount(),
            ],
        ];
    }

    private function resolveImportFormat(string $format): ImportFormat
    {
        return ImportFormat::from(strtolower($format));
    }

    private function resolveImportMode(string $mode): ImportMode
    {
        return ImportMode::from(strtolower($mode));
    }

    private function resolveImportStrategy(string $strategy): ImportStrategy
    {
        return ImportStrategy::from(strtolower($strategy));
    }
}
