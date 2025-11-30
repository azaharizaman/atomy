<?php

declare(strict_types=1);

namespace Nexus\Import\Services;

use Nexus\Import\Contracts\{
    ImportProcessorInterface,
    ImportHandlerInterface,
    FieldMapperInterface,
    ImportValidatorInterface,
    DuplicateDetectorInterface,
    TransactionManagerInterface
};
use Nexus\Import\ValueObjects\{
    ImportDefinition,
    ImportResult,
    ImportStrategy,
    ImportMode,
    ImportError,
    ErrorSeverity,
    FieldMapping,
    ValidationRule
};
use Nexus\Import\Core\Engine\{ErrorCollector, BatchProcessor};
use Nexus\Import\Exceptions\ImportException;
use Psr\Log\LoggerInterface;

/**
 * Import processor implementation
 * 
 * Orchestrates the import pipeline: map → validate → detect duplicates → persist.
 * Enforces transaction strategies (TRANSACTIONAL, BATCH, STREAM).
 */
final readonly class ImportProcessor implements ImportProcessorInterface
{
    public function __construct(
        private FieldMapperInterface $fieldMapper,
        private ImportValidatorInterface $validator,
        private DuplicateDetectorInterface $duplicateDetector,
        private LoggerInterface $logger
    ) {}

    public function process(
        ImportDefinition $definition,
        ImportHandlerInterface $handler,
        array $mappings,
        ImportMode $mode,
        ImportStrategy $strategy,
        ?TransactionManagerInterface $transactionManager = null,
        array $validationRules = []
    ): ImportResult {
        $this->logger->info('Starting import process', [
            'rows' => $definition->getRowCount(),
            'mode' => $mode->name,
            'strategy' => $strategy->name
        ]);

        // Validate mappings
        $mappingErrors = $this->fieldMapper->validateMappings($definition, $mappings);
        if (!empty($mappingErrors)) {
            return new ImportResult(
                successCount: 0,
                failedCount: 0,
                skippedCount: 0,
                errors: array_map(
                    fn($msg) => new ImportError(null, null, ErrorSeverity::CRITICAL, $msg),
                    $mappingErrors
                )
            );
        }

        return match($strategy) {
            ImportStrategy::TRANSACTIONAL => $this->processTransactional(
                $definition,
                $handler,
                $mappings,
                $mode,
                $transactionManager,
                $validationRules
            ),
            ImportStrategy::BATCH => $this->processBatch(
                $definition,
                $handler,
                $mappings,
                $mode,
                $transactionManager,
                $validationRules
            ),
            ImportStrategy::STREAM => $this->processStream(
                $definition,
                $handler,
                $mappings,
                $mode,
                $validationRules
            )
        };
    }

    /**
     * TRANSACTIONAL: Single transaction, rollback on any critical error
     */
    private function processTransactional(
        ImportDefinition $definition,
        ImportHandlerInterface $handler,
        array $mappings,
        ImportMode $mode,
        ?TransactionManagerInterface $transactionManager,
        array $validationRules
    ): ImportResult {
        if ($transactionManager === null) {
            throw new ImportException('TransactionManager required for TRANSACTIONAL strategy');
        }

        $errorCollector = new ErrorCollector();
        $successCount = 0;
        $skippedCount = 0;

        // Type guard for PHPStan
        $tm = $transactionManager;
        $tm->begin();

        try {
            // Detect internal duplicates first
            $uniqueKeyFields = $handler->getUniqueKeyFields();
            if (!empty($uniqueKeyFields)) {
                $duplicateErrors = $this->duplicateDetector->detectInternal(
                    $definition->rows,
                    $uniqueKeyFields
                );
                $errorCollector->addErrors($duplicateErrors);
            }

            foreach ($definition->rows as $index => $sourceRow) {
                $rowNumber = $index + 1;

                // Skip if row has duplicate errors
                if (!empty($errorCollector->getErrorsForRow($rowNumber))) {
                    $skippedCount++;
                    continue;
                }

                $result = $this->processRow(
                    $sourceRow,
                    $rowNumber,
                    $mappings,
                    $validationRules,
                    $handler,
                    $mode
                );

                $errorCollector->addErrors($result['errors']);

                if ($result['success']) {
                    $successCount++;
                } else {
                    $skippedCount++;
                }
            }

            // Check for critical errors before commit
            if ($errorCollector->hasCriticalErrors()) {
                $tm->rollback();
                
                return new ImportResult(
                    successCount: 0,
                    failedCount: $successCount + $skippedCount,
                    skippedCount: 0,
                    errors: $errorCollector->getAllErrors()
                );
            }

            $tm->commit();

            return new ImportResult(
                successCount: $successCount,
                failedCount: 0,
                skippedCount: $skippedCount,
                errors: $errorCollector->getAllErrors()
            );

        } catch (\Throwable $e) {
            $tm->rollback();
            
            $this->logger->error('Transaction rollback due to error', [
                'error' => $e->getMessage()
            ]);

            throw new ImportException(
                "Import failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * BATCH: Transaction per batch, continue on batch failure
     */
    private function processBatch(
        ImportDefinition $definition,
        ImportHandlerInterface $handler,
        array $mappings,
        ImportMode $mode,
        ?TransactionManagerInterface $transactionManager,
        array $validationRules
    ): ImportResult {
        $batchSize = BatchProcessor::getRecommendedBatchSize(ImportStrategy::BATCH);
        $batchProcessor = new BatchProcessor($batchSize);
        
        $errorCollector = new ErrorCollector();
        $successCount = 0;
        $skippedCount = 0;

        $batchProcessor->process(
            $definition->rows,
            function(array $batch, int $batchNumber) use (
                &$successCount,
                &$skippedCount,
                $errorCollector,
                $mappings,
                $validationRules,
                $handler,
                $mode,
                $transactionManager
            ) {
                $tm = $transactionManager;
                if ($tm !== null) {
                    $tm->begin();
                }

                try {
                    foreach ($batch as $index => $sourceRow) {
                        $rowNumber = $index + 1;

                        $result = $this->processRow(
                            $sourceRow,
                            $rowNumber,
                            $mappings,
                            $validationRules,
                            $handler,
                            $mode
                        );

                        $errorCollector->addErrors($result['errors']);

                        if ($result['success']) {
                            $successCount++;
                        } else {
                            $skippedCount++;
                        }
                    }

                    if ($tm !== null) {
                        $tm->commit();
                    }

                } catch (\Throwable $e) {
                    if ($tm !== null) {
                        $tm->rollback();
                    }

                    $errorCollector->addGlobalError(
                        "Batch #{$batchNumber} failed: {$e->getMessage()}",
                        ErrorSeverity::CRITICAL
                    );

                    $this->logger->error("Batch #{$batchNumber} failed", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        );

        return new ImportResult(
            successCount: $successCount,
            failedCount: 0,
            skippedCount: $skippedCount,
            errors: $errorCollector->getAllErrors()
        );
    }

    /**
     * STREAM: No transaction wrapper, process row-by-row
     */
    private function processStream(
        ImportDefinition $definition,
        ImportHandlerInterface $handler,
        array $mappings,
        ImportMode $mode,
        array $validationRules
    ): ImportResult {
        $errorCollector = new ErrorCollector();
        $successCount = 0;
        $skippedCount = 0;

        foreach ($definition->rows as $index => $sourceRow) {
            $rowNumber = $index + 1;

            try {
                $result = $this->processRow(
                    $sourceRow,
                    $rowNumber,
                    $mappings,
                    $validationRules,
                    $handler,
                    $mode
                );

                $errorCollector->addErrors($result['errors']);

                if ($result['success']) {
                    $successCount++;
                } else {
                    $skippedCount++;
                }

            } catch (\Throwable $e) {
                $errorCollector->addError(new ImportError(
                    rowNumber: $rowNumber,
                    field: null,
                    severity: ErrorSeverity::ERROR,
                    message: "Row processing failed: {$e->getMessage()}"
                ));
                
                $skippedCount++;
            }
        }

        return new ImportResult(
            successCount: $successCount,
            failedCount: 0,
            skippedCount: $skippedCount,
            errors: $errorCollector->getAllErrors()
        );
    }

    /**
     * Process a single row
     * 
     * @return array{success: bool, errors: ImportError[]}
     */
    private function processRow(
        array $sourceRow,
        int $rowNumber,
        array $mappings,
        array $validationRules,
        ImportHandlerInterface $handler,
        ImportMode $mode
    ): array {
        $errors = [];

        // 1. Map fields with transformations
        $mapResult = $this->fieldMapper->map($sourceRow, $mappings, $rowNumber);
        $targetData = $mapResult['data'];
        $errors = array_merge($errors, $mapResult['errors']);

        // 2. Validate mapped data
        $validationErrors = $this->validator->validateRow($targetData, $validationRules, $rowNumber);
        $errors = array_merge($errors, $validationErrors);

        // 3. Detect external duplicates
        $uniqueKeyFields = $handler->getUniqueKeyFields();
        if (!empty($uniqueKeyFields) && $mode->canCreate()) {
            $duplicateError = $this->duplicateDetector->detectExternal(
                $targetData,
                $uniqueKeyFields,
                fn($data) => $handler->exists($data),
                $rowNumber
            );
            
            if ($duplicateError !== null) {
                $errors[] = $duplicateError;
            }
        }

        // 4. Check if row has critical errors
        foreach ($errors as $error) {
            if ($error->severity === ErrorSeverity::CRITICAL || $error->severity === ErrorSeverity::ERROR) {
                return ['success' => false, 'errors' => $errors];
            }
        }

        // 5. Persist via handler
        try {
            $handler->handle($targetData, $mode);
            return ['success' => true, 'errors' => $errors];
            
        } catch (\Throwable $e) {
            $errors[] = new ImportError(
                rowNumber: $rowNumber,
                field: null,
                severity: ErrorSeverity::ERROR,
                message: "Persistence failed: {$e->getMessage()}"
            );
            
            return ['success' => false, 'errors' => $errors];
        }
    }
}
