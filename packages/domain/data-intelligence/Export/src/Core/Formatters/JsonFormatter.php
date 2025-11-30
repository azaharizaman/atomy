<?php

declare(strict_types=1);

namespace Nexus\Export\Core\Formatters;

use Generator;
use Nexus\Export\Contracts\ExportFormatterInterface;
use Nexus\Export\Exceptions\FormatterException;
use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportFormat;

/**
 * JSON formatter implementation
 * 
 * Converts ExportDefinition to JSON format with streaming support.
 * Uses JSON_PRETTY_PRINT and JSON_UNESCAPED_UNICODE for readability.
 */
final class JsonFormatter implements ExportFormatterInterface
{
    /**
     * @param int $options JSON encoding options
     */
    public function __construct(
        private readonly int $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ) {}

    /**
     * Format export definition as JSON string
     * 
     * @throws FormatterException
     */
    public function format(ExportDefinition $definition): string
    {
        try {
            return $definition->toJson();
        } catch (\Throwable $e) {
            throw new FormatterException("JSON formatting failed: {$e->getMessage()}", previous: $e);
        }
    }

    /**
     * Stream export definition as JSON generator
     * 
     * For JSON, streaming means yielding the entire JSON in chunks.
     * True line-by-line streaming is not possible for valid JSON.
     * 
     * @return Generator<string>
     * @throws FormatterException
     */
    public function stream(ExportDefinition $definition): Generator
    {
        try {
            $json = $this->format($definition);
            
            // Yield in 8KB chunks
            $chunkSize = 8192;
            $offset = 0;
            $length = strlen($json);

            while ($offset < $length) {
                yield substr($json, $offset, $chunkSize);
                $offset += $chunkSize;
            }
            
        } catch (\Throwable $e) {
            throw new FormatterException("JSON streaming failed: {$e->getMessage()}", previous: $e);
        }
    }

    /**
     * Get supported export format
     */
    public function getFormat(): ExportFormat
    {
        return ExportFormat::JSON;
    }

    /**
     * Check if streaming is supported (limited for JSON)
     */
    public function supportsStreaming(): bool
    {
        return true; // Chunked streaming only
    }

    /**
     * Check if formatter requires external service
     */
    public function requiresExternalService(): bool
    {
        return false;
    }

    /**
     * Get required schema version
     */
    public function requiresSchemaVersion(): string
    {
        return '>=1.0';
    }
}
