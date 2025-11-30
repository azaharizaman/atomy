<?php

declare(strict_types=1);

namespace Nexus\Export\Core\Formatters;

use Generator;
use Nexus\Export\Contracts\ExportFormatterInterface;
use Nexus\Export\Exceptions\FormatterException;
use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportFormat;
use Nexus\Export\ValueObjects\ExportSection;
use Nexus\Export\ValueObjects\TableStructure;

/**
 * CSV formatter implementation
 * 
 * Converts ExportDefinition to CSV format with streaming support.
 * Supports large datasets (100K+ rows) via generators.
 */
final readonly class CsvFormatter implements ExportFormatterInterface
{
    /**
     * @param string $delimiter Column delimiter (default: comma)
     * @param string $enclosure Field enclosure character (default: double quote)
     * @param string $escape Escape character (default: backslash)
     */
    public function __construct(
        private string $delimiter = ',',
        private string $enclosure = '"',
        private string $escape = '\\'
    ) {}

    /**
     * Format export definition as CSV string
     * 
     * @throws FormatterException
     */
    public function format(ExportDefinition $definition): string
    {
        try {
            $output = '';
            $handle = fopen('php://temp', 'r+');
            
            if ($handle === false) {
                throw new FormatterException('Failed to open temporary stream');
            }

            foreach ($this->stream($definition) as $line) {
                $output .= $line;
            }

            fclose($handle);
            
            return $output;
        } catch (\Throwable $e) {
            throw new FormatterException("CSV formatting failed: {$e->getMessage()}", previous: $e);
        }
    }

    /**
     * Stream export definition as CSV generator
     * 
     * @return Generator<string> CSV lines
     * @throws FormatterException
     */
    public function stream(ExportDefinition $definition): Generator
    {
        try {
            // CSV exports typically only include tables
            // Metadata and text sections are omitted or prepended as comments
            
            // Optionally add metadata as comments
            if ($definition->metadata->title) {
                yield $this->formatComment("Title: {$definition->metadata->title}");
            }
            if ($definition->metadata->generatedAt) {
                yield $this->formatComment("Generated: {$definition->metadata->generatedAt->format('Y-m-d H:i:s')}");
            }
            
            yield $this->formatComment(''); // Blank line
            
            // Process structure
            foreach ($definition->structure as $section) {
                yield from $this->formatSection($section);
            }
            
        } catch (\Throwable $e) {
            throw new FormatterException("CSV streaming failed: {$e->getMessage()}", previous: $e);
        }
    }

    /**
     * Get supported export format
     */
    public function getFormat(): ExportFormat
    {
        return ExportFormat::CSV;
    }

    /**
     * Check if streaming is supported (always true for CSV)
     */
    public function supportsStreaming(): bool
    {
        return true;
    }

    /**
     * Check if formatter requires external service (always false for CSV)
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

    /**
     * Format section as CSV rows
     * 
     * @return Generator<string>
     */
    private function formatSection(ExportSection $section): Generator
    {
        // Add section header as comment
        if ($section->name) {
            yield $this->formatComment(str_repeat('=', $section->level) . ' ' . $section->name);
        }

        foreach ($section->items as $item) {
            if ($item instanceof TableStructure) {
                yield from $this->formatTable($item);
                yield "\n"; // Blank line after table
            } elseif ($item instanceof ExportSection) {
                yield from $this->formatSection($item);
            } elseif (is_string($item)) {
                yield $this->formatComment($item);
            }
        }
    }

    /**
     * Format table as CSV rows
     * 
     * @return Generator<string>
     */
    private function formatTable(TableStructure $table): Generator
    {
        // Headers
        yield $this->formatRow($table->headers);

        // Rows
        foreach ($table->rows as $row) {
            yield $this->formatRow($row);
        }

        // Footers (if any)
        if (!empty($table->footers)) {
            yield $this->formatRow($table->footers);
        }
    }

    /**
     * Format single row as CSV line
     * 
     * @param array<int, scalar|null> $row
     */
    private function formatRow(array $row): string
    {
        $handle = fopen('php://temp', 'r+');
        
        if ($handle === false) {
            throw new FormatterException('Failed to open temporary stream for row');
        }

        fputcsv($handle, $row, $this->delimiter, $this->enclosure, $this->escape);
        rewind($handle);
        
        $line = stream_get_contents($handle);
        fclose($handle);

        return $line !== false ? $line : '';
    }

    /**
     * Format comment line (CSV standard uses # prefix)
     */
    private function formatComment(string $text): string
    {
        return "# {$text}\n";
    }
}
