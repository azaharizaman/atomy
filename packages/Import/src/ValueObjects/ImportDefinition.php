<?php

declare(strict_types=1);

namespace Nexus\Import\ValueObjects;

/**
 * Immutable import definition value object
 * 
 * Intermediate representation of parsed import data.
 * Decouples file format from domain logic (mirrors ExportDefinition pattern).
 */
readonly class ImportDefinition
{
    /**
     * @param ImportMetadata $metadata Import metadata
     * @param array<string> $headers Column headers
     * @param array<array<string, mixed>> $rows Data rows (array of associative arrays)
     * @param array<string, mixed> $options Additional options for processing
     */
    public function __construct(
        public ImportMetadata $metadata,
        public array $headers,
        public array $rows,
        public array $options = []
    ) {
        $this->validateStructure();
    }

    /**
     * Validate definition structure
     * 
     * @throws \InvalidArgumentException
     */
    private function validateStructure(): void
    {
        if (empty($this->headers)) {
            throw new \InvalidArgumentException('Import definition must have at least one header');
        }

        // Validate each row has all header keys
        $headerCount = count($this->headers);
        foreach ($this->rows as $index => $row) {
            $rowKeys = array_keys($row);
            $missingHeaders = array_diff($this->headers, $rowKeys);
            
            if (!empty($missingHeaders)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Row %d is missing headers: %s',
                        $index + 1,
                        implode(', ', $missingHeaders)
                    )
                );
            }
        }
    }

    /**
     * Get total row count
     */
    public function getRowCount(): int
    {
        return count($this->rows);
    }

    /**
     * Get column count
     */
    public function getColumnCount(): int
    {
        return count($this->headers);
    }

    /**
     * Check if definition is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->rows);
    }

    /**
     * Get a specific row by index
     * 
     * @param int $index Zero-based row index
     * @return array<string, mixed>|null
     */
    public function getRow(int $index): ?array
    {
        return $this->rows[$index] ?? null;
    }

    /**
     * Get first N rows (for preview)
     * 
     * @return array<array<string, mixed>>
     */
    public function getFirstRows(int $limit = 10): array
    {
        return array_slice($this->rows, 0, $limit);
    }

    /**
     * Convert to JSON string
     */
    public function toJson(): string
    {
        return json_encode([
            'metadata' => $this->metadata->toArray(),
            'headers' => $this->headers,
            'row_count' => $this->getRowCount(),
            'column_count' => $this->getColumnCount(),
            'sample_rows' => $this->getFirstRows(5),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'metadata' => $this->metadata->toArray(),
            'headers' => $this->headers,
            'rows' => $this->rows,
            'options' => $this->options,
        ];
    }

    /**
     * Create from array (inverse of toArray)
     */
    public static function fromArray(array $data): self
    {
        $metadataData = $data['metadata'];
        
        $metadata = new ImportMetadata(
            originalFileName: $metadataData['file_name'],
            fileSize: $metadataData['file_size_bytes'],
            mimeType: $metadataData['mime_type'],
            uploadedAt: new \DateTimeImmutable($metadataData['uploaded_at']),
            uploadedBy: $metadataData['uploaded_by'] ?? null,
            tenantId: $metadataData['tenant_id'] ?? null
        );
        
        return new self(
            headers: $data['headers'],
            rows: $data['rows'],
            metadata: $metadata
        );
    }
}
