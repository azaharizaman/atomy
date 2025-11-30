<?php

declare(strict_types=1);

namespace Nexus\Import\Parsers;

use Nexus\Import\Contracts\ImportParserInterface;
use Nexus\Import\ValueObjects\ImportDefinition;
use Nexus\Import\ValueObjects\ImportMetadata;
use Nexus\Import\ValueObjects\ImportFormat;
use Nexus\Import\Exceptions\ParserException;

/**
 * JSON parser implementation
 * 
 * Parses JSON arrays to ImportDefinition.
 */
final class JsonParser implements ImportParserInterface
{
    public function parse(
        string $filePath,
        ImportMetadata $metadata
    ): ImportDefinition {
        if (!file_exists($filePath)) {
            throw new ParserException("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new ParserException("Failed to read file: {$filePath}");
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ParserException(
                "Invalid JSON: {$e->getMessage()}",
                previous: $e
            );
        }

        if (!is_array($data)) {
            throw new ParserException('JSON must contain an array of objects');
        }

        // Handle empty array
        if (empty($data)) {
            return new ImportDefinition(
                headers: [],
                rows: [],
                metadata: $metadata
            );
        }

        // Extract headers from first object
        $firstRow = reset($data);
        if (!is_array($firstRow)) {
            throw new ParserException('JSON must contain an array of objects');
        }

        $headers = array_keys($firstRow);
        
        // Normalize all rows
        $rows = array_map(
            fn($row) => $this->normalizeRow($row, $headers),
            $data
        );

        return new ImportDefinition(
            headers: $headers,
            rows: $rows,
            metadata: $metadata
        );
    }

    public function supports(ImportFormat $format): bool
    {
        return $format === ImportFormat::JSON;
    }

    public function parseStream(
        string $filePath,
        ImportMetadata $metadata,
        callable $callback,
        int $chunkSize = 100
    ): void {
        // JSON streaming is complex (requires line-delimited JSON or streaming parser)
        // For simplicity, parse entire file and chunk the result
        $definition = $this->parse($filePath, $metadata);
        
        $chunks = array_chunk($definition->rows, $chunkSize, true);
        
        foreach ($chunks as $index => $chunk) {
            $callback($chunk, $index + 1);
        }
    }

    /**
     * Normalize row to ensure all headers are present
     */
    private function normalizeRow(array $row, array $headers): array
    {
        $normalized = [];

        foreach ($headers as $header) {
            $normalized[$header] = $row[$header] ?? null;
        }

        return $normalized;
    }
}
