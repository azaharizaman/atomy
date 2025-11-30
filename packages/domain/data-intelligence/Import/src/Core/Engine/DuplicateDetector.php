<?php

declare(strict_types=1);

namespace Nexus\Import\Core\Engine;

use Nexus\Import\Contracts\DuplicateDetectorInterface;
use Nexus\Import\ValueObjects\ImportError;
use Nexus\Import\ValueObjects\ErrorSeverity;

/**
 * Duplicate detector implementation
 * 
 * Detects duplicate records within the import dataset (internal)
 * and against existing data (external via callback).
 */
final class DuplicateDetector implements DuplicateDetectorInterface
{
    /**
     * Internal hash storage for duplicate detection
     * @var array<string, int>
     */
    private array $internalHashes = [];

    public function detectInternal(
        array $rows,
        array $uniqueKeyFields
    ): array {
        $this->internalHashes = [];
        $duplicates = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;
            $hash = $this->generateHash($row, $uniqueKeyFields);

            if (isset($this->internalHashes[$hash])) {
                $duplicates[] = new ImportError(
                    rowNumber: $rowNumber,
                    field: implode(', ', $uniqueKeyFields),
                    severity: ErrorSeverity::ERROR,
                    message: sprintf(
                        'Duplicate row detected (matches row %d on fields: %s)',
                        $this->internalHashes[$hash],
                        implode(', ', $uniqueKeyFields)
                    ),
                    context: ['duplicate_values' => array_intersect_key(
                        $row,
                        array_flip($uniqueKeyFields)
                    )]
                );
            } else {
                $this->internalHashes[$hash] = $rowNumber;
            }
        }

        return $duplicates;
    }

    public function detectExternal(
        array $row,
        array $uniqueKeyFields,
        callable $existsCheck,
        int $rowNumber
    ): ?ImportError {
        $uniqueData = array_intersect_key($row, array_flip($uniqueKeyFields));

        // Skip check if any unique key field is empty
        foreach ($uniqueData as $value) {
            if ($value === null || $value === '') {
                return null;
            }
        }

        if ($existsCheck($uniqueData)) {
            return new ImportError(
                rowNumber: $rowNumber,
                field: implode(', ', $uniqueKeyFields),
                severity: ErrorSeverity::ERROR,
                message: sprintf(
                    'Record already exists with %s: %s',
                    implode(', ', array_keys($uniqueData)),
                    implode(', ', array_values($uniqueData))
                ),
                context: ['existing_values' => $uniqueData]
            );
        }

        return null;
    }

    public function hasDuplicates(
        array $rows,
        array $uniqueKeyFields
    ): bool {
        $seen = [];

        foreach ($rows as $row) {
            $hash = $this->generateHash($row, $uniqueKeyFields);

            if (isset($seen[$hash])) {
                return true;
            }

            $seen[$hash] = true;
        }

        return false;
    }

    public function reset(): void
    {
        $this->internalHashes = [];
    }

    /**
     * Generate hash for duplicate detection
     */
    private function generateHash(array $row, array $uniqueKeyFields): string
    {
        $values = [];

        foreach ($uniqueKeyFields as $field) {
            $values[] = $row[$field] ?? '';
        }

        // Normalize and hash
        $normalized = array_map(
            fn($v) => mb_strtolower(trim((string) $v), 'UTF-8'),
            $values
        );

        return hash('xxh128', implode('|', $normalized));
    }
}
