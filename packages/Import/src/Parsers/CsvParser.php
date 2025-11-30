<?php

declare(strict_types=1);

namespace Nexus\Import\Parsers;

use Nexus\Import\Contracts\ImportParserInterface;
use Nexus\Import\ValueObjects\ImportDefinition;
use Nexus\Import\ValueObjects\ImportMetadata;
use Nexus\Import\ValueObjects\ImportFormat;
use Nexus\Import\Exceptions\ParserException;

/**
 * CSV parser implementation
 * 
 * RFC 4180 compliant CSV parser with streaming support.
 */
final readonly class CsvParser implements ImportParserInterface
{
    public function __construct(
        private string $delimiter = ',',
        private string $enclosure = '"',
        private string $escape = '\\',
        private bool $hasHeader = true,
        private ?string $encoding = 'UTF-8'
    ) {}

    public function parse(
        string $filePath,
        ImportMetadata $metadata
    ): ImportDefinition {
        if (!file_exists($filePath)) {
            throw new ParserException("File not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new ParserException("File is not readable: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new ParserException("Failed to open file: {$filePath}");
        }

        try {
            $headers = $this->parseHeaders($handle);
            $rows = $this->parseRows($handle, $headers);

            return new ImportDefinition(
                headers: $headers,
                rows: $rows,
                metadata: $metadata
            );
        } catch (\Throwable $e) {
            throw new ParserException(
                "Failed to parse CSV: {$e->getMessage()}",
                previous: $e
            );
        } finally {
            fclose($handle);
        }
    }

    public function supports(ImportFormat $format): bool
    {
        return $format === ImportFormat::CSV;
    }

    public function parseStream(
        string $filePath,
        ImportMetadata $metadata,
        callable $callback,
        int $chunkSize = 100
    ): void {
        if (!file_exists($filePath)) {
            throw new ParserException("File not found: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new ParserException("Failed to open file: {$filePath}");
        }

        try {
            $headers = $this->parseHeaders($handle);
            $chunk = [];
            $rowNumber = 1;

            while (($data = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
                // Skip empty rows
                if ($this->isEmptyRow($data)) {
                    continue;
                }

                $row = $this->normalizeRow($data, $headers);
                $chunk[] = $row;

                if (count($chunk) >= $chunkSize) {
                    $callback($chunk, $rowNumber);
                    $chunk = [];
                }

                $rowNumber++;
            }

            // Process remaining rows
            if (!empty($chunk)) {
                $callback($chunk, $rowNumber);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Parse headers from CSV
     * 
     * @return string[]
     */
    private function parseHeaders($handle): array
    {
        if ($this->hasHeader) {
            $headers = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape);
            
            if ($headers === false) {
                throw new ParserException('Failed to read CSV headers');
            }

            return array_map('trim', $headers);
        }

        // Generate numeric headers if no header row
        $firstRow = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape);
        
        if ($firstRow === false) {
            throw new ParserException('Empty CSV file');
        }

        // Rewind to start after reading first row
        rewind($handle);
        
        return array_map(fn($i) => "Column " . ($i + 1), array_keys($firstRow));
    }

    /**
     * Parse all rows from CSV
     * 
     * @return array[]
     */
    private function parseRows($handle, array $headers): array
    {
        $rows = [];

        while (($data = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
            // Skip empty rows
            if ($this->isEmptyRow($data)) {
                continue;
            }

            $rows[] = $this->normalizeRow($data, $headers);
        }

        return $rows;
    }

    /**
     * Normalize row data to associative array
     */
    private function normalizeRow(array $data, array $headers): array
    {
        $row = [];
        $headerCount = count($headers);

        foreach ($headers as $index => $header) {
            $value = $data[$index] ?? null;
            
            // Convert encoding if needed
            if ($this->encoding !== null && $this->encoding !== 'UTF-8') {
                $value = mb_convert_encoding($value, 'UTF-8', $this->encoding);
            }
            
            $row[$header] = $value !== null ? trim($value) : null;
        }

        return $row;
    }

    /**
     * Check if row is empty
     */
    private function isEmptyRow(array $data): bool
    {
        foreach ($data as $value) {
            if ($value !== null && trim($value) !== '') {
                return false;
            }
        }

        return true;
    }
}
