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
 * Plain text formatter implementation
 * 
 * Converts ExportDefinition to ASCII-formatted plain text with streaming support.
 * Creates tabular layouts using str_pad for alignment.
 */
final class TxtFormatter implements ExportFormatterInterface
{
    /**
     * @param int $pageWidth Maximum line width in characters
     */
    public function __construct(
        private readonly int $pageWidth = 120
    ) {}

    /**
     * Format export definition as plain text string
     * 
     * @throws FormatterException
     */
    public function format(ExportDefinition $definition): string
    {
        try {
            $output = '';
            
            foreach ($this->stream($definition) as $line) {
                $output .= $line;
            }

            return $output;
        } catch (\Throwable $e) {
            throw new FormatterException("TXT formatting failed: {$e->getMessage()}", previous: $e);
        }
    }

    /**
     * Stream export definition as text generator
     * 
     * @return Generator<string>
     * @throws FormatterException
     */
    public function stream(ExportDefinition $definition): Generator
    {
        try {
            // Header
            yield $this->formatLine(str_repeat('=', $this->pageWidth));
            yield $this->formatLine($definition->metadata->title);
            
            if ($definition->metadata->author) {
                yield $this->formatLine("Author: {$definition->metadata->author}");
            }
            
            if ($definition->metadata->generatedAt) {
                yield $this->formatLine("Generated: {$definition->metadata->generatedAt->format('Y-m-d H:i:s')}");
            }
            
            yield $this->formatLine(str_repeat('=', $this->pageWidth));
            yield $this->formatLine('');

            // Structure
            foreach ($definition->structure as $section) {
                yield from $this->formatSection($section);
            }

            // Footer
            yield $this->formatLine('');
            yield $this->formatLine(str_repeat('-', $this->pageWidth));
            yield $this->formatLine('End of Report');
            
        } catch (\Throwable $e) {
            throw new FormatterException("TXT streaming failed: {$e->getMessage()}", previous: $e);
        }
    }

    /**
     * Get supported export format
     */
    public function getFormat(): ExportFormat
    {
        return ExportFormat::TXT;
    }

    /**
     * Check if streaming is supported
     */
    public function supportsStreaming(): bool
    {
        return true;
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

    /**
     * Format section as text lines
     * 
     * @return Generator<string>
     */
    private function formatSection(ExportSection $section, int $indent = 0): Generator
    {
        $indentStr = str_repeat('  ', $indent);

        // Section header
        if ($section->name) {
            $marker = str_repeat('#', $section->level + 1);
            yield $this->formatLine("{$indentStr}{$marker} {$section->name}");
            yield $this->formatLine('');
        }

        // Section items
        foreach ($section->items as $item) {
            if ($item instanceof TableStructure) {
                yield from $this->formatTable($item, $indent);
                yield $this->formatLine('');
            } elseif ($item instanceof ExportSection) {
                yield from $this->formatSection($item, $indent + 1);
            } elseif (is_string($item)) {
                yield $this->formatLine("{$indentStr}{$item}");
            } elseif (is_array($item)) {
                foreach ($item as $key => $value) {
                    yield $this->formatLine("{$indentStr}{$key}: {$value}");
                }
                yield $this->formatLine('');
            }
        }
    }

    /**
     * Format table as text lines with ASCII borders
     * 
     * @return Generator<string>
     */
    private function formatTable(TableStructure $table, int $indent = 0): Generator
    {
        $indentStr = str_repeat('  ', $indent);

        // Calculate column widths
        $widths = $this->calculateColumnWidths($table);

        // Top border
        yield $this->formatLine($indentStr . $this->formatBorder($widths));

        // Headers
        yield $this->formatLine($indentStr . $this->formatRow($table->headers, $widths));

        // Header separator
        yield $this->formatLine($indentStr . $this->formatBorder($widths, '='));

        // Rows
        foreach ($table->rows as $row) {
            yield $this->formatLine($indentStr . $this->formatRow($row, $widths));
        }

        // Footers
        if (!empty($table->footers)) {
            yield $this->formatLine($indentStr . $this->formatBorder($widths, '='));
            yield $this->formatLine($indentStr . $this->formatRow($table->footers, $widths));
        }

        // Bottom border
        yield $this->formatLine($indentStr . $this->formatBorder($widths));
    }

    /**
     * Calculate optimal column widths
     * 
     * @return int[] Column widths
     */
    private function calculateColumnWidths(TableStructure $table): array
    {
        $widths = [];

        // Initialize with header widths
        foreach ($table->headers as $index => $header) {
            $widths[$index] = strlen((string) $header);
        }

        // Update with row widths
        foreach ($table->rows as $row) {
            foreach ($row as $index => $cell) {
                $widths[$index] = max($widths[$index] ?? 0, strlen((string) $cell));
            }
        }

        // Update with footer widths
        if (!empty($table->footers)) {
            foreach ($table->footers as $index => $footer) {
                $widths[$index] = max($widths[$index] ?? 0, strlen((string) $footer));
            }
        }

        // Apply minimum width and maximum width constraints
        foreach ($widths as $index => $width) {
            $widths[$index] = min(max($width, 5), 40); // Min 5, Max 40
        }

        return $widths;
    }

    /**
     * Format table row
     * 
     * @param array<int, scalar|null> $cells
     * @param int[] $widths
     */
    private function formatRow(array $cells, array $widths): string
    {
        $formatted = [];

        foreach ($cells as $index => $cell) {
            $width = $widths[$index] ?? 10;
            $text = (string) $cell;
            
            // Truncate if too long
            if (strlen($text) > $width) {
                $text = substr($text, 0, $width - 3) . '...';
            }

            $formatted[] = str_pad($text, $width);
        }

        return '| ' . implode(' | ', $formatted) . ' |';
    }

    /**
     * Format table border
     * 
     * @param int[] $widths
     */
    private function formatBorder(array $widths, string $char = '-'): string
    {
        $segments = [];

        foreach ($widths as $width) {
            $segments[] = str_repeat($char, $width);
        }

        return '+' . str_repeat($char, 1) . implode(str_repeat($char, 1) . '+' . str_repeat($char, 1), $segments) . str_repeat($char, 1) . '+';
    }

    /**
     * Format single line with newline
     */
    private function formatLine(string $text): string
    {
        return $text . "\n";
    }
}
