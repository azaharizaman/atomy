<?php

declare(strict_types=1);

namespace Nexus\Export\ValueObjects;

/**
 * Table structure value object
 * 
 * Represents tabular data with headers, rows, and optional footers.
 * Used for financial statements, aging reports, line item details.
 */
final readonly class TableStructure
{
    /**
     * @param array<string> $headers Column headers
     * @param array<array<string>> $rows Data rows (array of arrays)
     * @param array<string>|null $footers Footer row (totals, summaries)
     * @param array<int>|null $columnWidths Column width hints (percentages or pixels)
     * @param array<string, mixed> $styling Table styling hints
     */
    public function __construct(
        public array $headers,
        public array $rows,
        public ?array $footers = null,
        public ?array $columnWidths = null,
        public array $styling = [],
    ) {
        if (empty($headers)) {
            throw new \InvalidArgumentException('Table must have at least one header');
        }

        $columnCount = count($headers);

        // Validate all rows have same column count
        foreach ($rows as $index => $row) {
            if (count($row) !== $columnCount) {
                throw new \InvalidArgumentException(
                    "Row {$index} has " . count($row) . " columns, expected {$columnCount}"
                );
            }
        }

        // Validate footer if present
        if ($footers !== null && count($footers) !== $columnCount) {
            throw new \InvalidArgumentException(
                'Footer has ' . count($footers) . " columns, expected {$columnCount}"
            );
        }
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            headers: $data['headers'] ?? [],
            rows: $data['rows'] ?? [],
            footers: $data['footers'] ?? null,
            columnWidths: $data['column_widths'] ?? null,
            styling: $data['styling'] ?? [],
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'type' => 'table',
            'headers' => $this->headers,
            'rows' => $this->rows,
            'footers' => $this->footers,
            'column_widths' => $this->columnWidths,
            'styling' => $this->styling,
        ];
    }

    /**
     * Get column count
     */
    public function getColumnCount(): int
    {
        return count($this->headers);
    }

    /**
     * Get row count
     */
    public function getRowCount(): int
    {
        return count($this->rows);
    }

    /**
     * Check if table has footer
     */
    public function hasFooter(): bool
    {
        return $this->footers !== null;
    }
}
