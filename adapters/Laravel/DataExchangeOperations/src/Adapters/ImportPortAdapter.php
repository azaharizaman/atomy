<?php

declare(strict_types=1);

namespace Nexus\Laravel\DataExchangeOperations\Adapters;

use Nexus\DataExchangeOperations\Contracts\DataImportPortInterface;
use Nexus\DataExchangeOperations\DTOs\DataOnboardingRequest;
use Nexus\Import\Contracts\ImportHandlerInterface;
use Nexus\Import\Contracts\TransactionManagerInterface;
use Nexus\Import\Services\ImportManager;
use Nexus\Import\ValueObjects\ImportFormat;
use Nexus\Import\ValueObjects\ImportMetadata;
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

        $mappings = $this->normalizeMappings($request->options['mappings'] ?? []);
        $validationRules = $this->normalizeValidationRules($request->options['validation_rules'] ?? []);
        $tenantHash = hash('sha256', $request->tenantId);
        $sourcePathHash = hash('sha256', $request->sourcePath);
        $sourceBasename = basename($request->sourcePath);

        $this->logger->info('Executing import adapter.', [
            'task_id' => $request->taskId,
            'tenant_hash' => $tenantHash,
            'source_path_hash' => $sourcePathHash,
            'source_basename' => $sourceBasename,
            'format' => $format->value,
            'mode' => $mode->value,
            'strategy' => $strategy->value,
        ]);

        $metadata = new ImportMetadata(
            originalFileName: basename($request->sourcePath),
            fileSize: is_file($request->sourcePath) ? ((int) (filesize($request->sourcePath) ?: 0)) : 0,
            mimeType: is_file($request->sourcePath) ? (mime_content_type($request->sourcePath) ?: 'application/octet-stream') : 'application/octet-stream',
            uploadedAt: new \DateTimeImmutable(),
            uploadedBy: null,
            tenantId: $request->tenantId,
        );

        $result = $this->importManager->import(
            filePath: $request->sourcePath,
            format: $format,
            handler: $handler,
            mappings: $mappings,
            mode: $mode,
            strategy: $strategy,
            transactionManager: $this->transactionManager,
            validationRules: $validationRules,
            metadata: $metadata,
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
        $normalized = strtolower($format);

        try {
            return ImportFormat::from($normalized);
        } catch (\ValueError $e) {
            $allowed = implode(', ', array_map(static fn (ImportFormat $case): string => $case->value, ImportFormat::cases()));
            throw new \InvalidArgumentException(
                sprintf('Invalid import format: %s. Allowed values: %s', $format, $allowed),
                previous: $e
            );
        }
    }

    private function resolveImportMode(string $mode): ImportMode
    {
        $normalized = strtolower($mode);

        try {
            return ImportMode::from($normalized);
        } catch (\ValueError $e) {
            $allowed = implode(', ', array_map(static fn (ImportMode $case): string => $case->value, ImportMode::cases()));
            throw new \InvalidArgumentException(
                sprintf('Invalid import mode: %s. Allowed values: %s', $mode, $allowed),
                previous: $e
            );
        }
    }

    private function resolveImportStrategy(string $strategy): ImportStrategy
    {
        $normalized = strtolower($strategy);

        try {
            return ImportStrategy::from($normalized);
        } catch (\ValueError $e) {
            $allowed = implode(', ', array_map(static fn (ImportStrategy $case): string => $case->value, ImportStrategy::cases()));
            throw new \InvalidArgumentException(
                sprintf('Invalid import strategy: %s. Allowed values: %s', $strategy, $allowed),
                previous: $e
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function normalizeMappings(mixed $mappings): array
    {
        if (!is_array($mappings)) {
            throw new \InvalidArgumentException('Onboarding option "mappings" must be an array.');
        }

        $normalized = [];
        foreach ($mappings as $source => $target) {
            if (!is_string($source) || trim($source) === '' || !is_string($target) || trim($target) === '') {
                throw new \InvalidArgumentException('Onboarding option "mappings" must be a non-empty string-to-string map.');
            }

            $normalized[$source] = $target;
        }

        return $normalized;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    private function normalizeValidationRules(mixed $rules): array
    {
        if (!is_array($rules)) {
            throw new \InvalidArgumentException('Onboarding option "validation_rules" must be an array.');
        }

        $normalized = [];
        foreach ($rules as $field => $fieldRules) {
            if (!is_string($field) || trim($field) === '') {
                throw new \InvalidArgumentException('Onboarding option "validation_rules" must use non-empty string keys.');
            }

            if (is_string($fieldRules)) {
                $normalized[$field] = $fieldRules;
                continue;
            }

            if (!is_array($fieldRules)) {
                throw new \InvalidArgumentException('Onboarding option "validation_rules" values must be strings or arrays of strings.');
            }

            foreach ($fieldRules as $rule) {
                if (!is_string($rule)) {
                    throw new \InvalidArgumentException('Onboarding option "validation_rules" arrays must contain only strings.');
                }
            }

            $normalized[$field] = $fieldRules;
        }

        return $normalized;
    }
}
