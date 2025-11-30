<?php

declare(strict_types=1);

namespace Nexus\Import\Services;

use Nexus\Import\Contracts\{
    ImportParserInterface,
    ImportProcessorInterface,
    ImportHandlerInterface,
    ImportAuthorizerInterface,
    ImportContextInterface,
    TransactionManagerInterface
};
use Nexus\Import\ValueObjects\{
    ImportFormat,
    ImportMode,
    ImportStrategy,
    ImportMetadata,
    ImportDefinition,
    ImportResult,
    FieldMapping,
    ValidationRule
};
use Nexus\Import\Exceptions\{
    UnsupportedFormatException,
    ImportAuthorizationException
};
use Psr\Log\LoggerInterface;

/**
 * Import manager - Main public API
 * 
 * Orchestrates the complete import pipeline:
 * 1. Authorization check
 * 2. Parse file to ImportDefinition
 * 3. Validate definition structure
 * 4. Process import via ImportProcessor
 * 5. Return ImportResult
 */
final class ImportManager
{
    /**
     * @var array<ImportFormat, ImportParserInterface>
     */
    private array $parsers = [];

    public function __construct(
        private readonly ImportProcessorInterface $processor,
        private readonly ?ImportAuthorizerInterface $authorizer,
        private readonly ?ImportContextInterface $context,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Register a parser for a specific format
     */
    public function registerParser(ImportFormat $format, ImportParserInterface $parser): void
    {
        $this->parsers[$format->value] = $parser;
    }

    /**
     * Import data from file
     * 
     * @param FieldMapping[] $mappings
     * @param ValidationRule[] $validationRules
     */
    public function import(
        string $filePath,
        ImportFormat $format,
        ImportHandlerInterface $handler,
        array $mappings,
        ImportMode $mode = ImportMode::CREATE,
        ImportStrategy $strategy = ImportStrategy::BATCH,
        ?TransactionManagerInterface $transactionManager = null,
        array $validationRules = [],
        ?ImportMetadata $metadata = null
    ): ImportResult {
        // 1. Authorization check
        if ($this->authorizer !== null) {
            $this->authorizer->assertCanImport($handler, $mode, $this->context);
        }

        // 2. Create metadata if not provided
        if ($metadata === null) {
            $metadata = new ImportMetadata(
                originalFileName: basename($filePath),
                fileSize: filesize($filePath) ?: 0,
                mimeType: mime_content_type($filePath) ?: 'application/octet-stream',
                uploadedAt: new \DateTimeImmutable(),
                uploadedBy: $this->context?->getUserId()
            );
        }

        // 3. Parse file to definition
        $definition = $this->parse($filePath, $format, $metadata);

        $this->logger->info('Import started', [
            'file' => $metadata->originalFileName,
            'format' => $format->name,
            'rows' => $definition->getRowCount(),
            'mode' => $mode->name,
            'strategy' => $strategy->name
        ]);

        // 4. Process import
        $result = $this->processor->process(
            definition: $definition,
            handler: $handler,
            mappings: $mappings,
            mode: $mode,
            strategy: $strategy,
            transactionManager: $transactionManager,
            validationRules: $validationRules
        );

        $this->logger->info('Import completed', [
            'success' => $result->successCount,
            'failed' => $result->failedCount,
            'skipped' => $result->skippedCount,
            'errors' => $result->getErrorCount()
        ]);

        return $result;
    }

    /**
     * Parse file to ImportDefinition without processing
     */
    public function parse(
        string $filePath,
        ImportFormat $format,
        ?ImportMetadata $metadata = null
    ): ImportDefinition {
        $parser = $this->getParser($format);

        if ($metadata === null) {
            $metadata = new ImportMetadata(
                originalFileName: basename($filePath),
                fileSize: filesize($filePath) ?: 0,
                mimeType: mime_content_type($filePath) ?: 'application/octet-stream',
                uploadedAt: new \DateTimeImmutable()
            );
        }

        return $parser->parse($filePath, $metadata);
    }

    /**
     * Validate import without persisting data (dry run)
     * 
     * @param FieldMapping[] $mappings
     * @param ValidationRule[] $validationRules
     */
    public function validateImport(
        string $filePath,
        ImportFormat $format,
        ImportHandlerInterface $handler,
        array $mappings,
        array $validationRules = [],
        ?ImportMetadata $metadata = null
    ): ImportResult {
        // Parse file
        $definition = $this->parse($filePath, $format, $metadata);

        // Create a dry-run processor that doesn't persist
        // This would require a separate implementation or a flag in the handler
        // For now, we just validate structure and mappings
        
        $this->logger->info('Validation started', [
            'file' => $metadata?->originalFileName ?? basename($filePath),
            'format' => $format->name,
            'rows' => $definition->getRowCount()
        ]);

        // TODO: Implement validation-only processing
        // This would involve running the mapping and validation pipeline
        // without calling the handler's persist methods

        return new ImportResult(
            successCount: 0,
            failedCount: 0,
            skippedCount: 0,
            errors: []
        );
    }

    /**
     * Check if a format is supported
     */
    public function supportsFormat(ImportFormat $format): bool
    {
        return isset($this->parsers[$format->value]);
    }

    /**
     * Get supported formats
     * 
     * @return ImportFormat[]
     */
    public function getSupportedFormats(): array
    {
        return array_map(
            fn($key) => ImportFormat::from($key),
            array_keys($this->parsers)
        );
    }

    /**
     * Get parser for format
     */
    private function getParser(ImportFormat $format): ImportParserInterface
    {
        if (!isset($this->parsers[$format->value])) {
            throw new UnsupportedFormatException(
                "No parser registered for format: {$format->value}"
            );
        }

        return $this->parsers[$format->value];
    }
}
