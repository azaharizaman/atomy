<?php

declare(strict_types=1);

namespace Nexus\Import\Core\Engine;

use Nexus\Import\ValueObjects\ImportStrategy;

/**
 * Batch processor
 * 
 * Processes large datasets in configurable batches for memory efficiency.
 */
final class BatchProcessor
{
    private const DEFAULT_BATCH_SIZE = 500;
    private const STREAM_BATCH_SIZE = 100;
    private const TRANSACTIONAL_BATCH_SIZE = 1000;

    public function __construct(
        private readonly int $batchSize = self::DEFAULT_BATCH_SIZE
    ) {}

    /**
     * Process dataset in batches
     * 
     * @param array $dataset
     * @param callable $processor Callback: fn(array $batch, int $batchNumber) => void
     */
    public function process(array $dataset, callable $processor): void
    {
        $batches = $this->chunk($dataset);

        foreach ($batches as $index => $batch) {
            $batchNumber = $index + 1;
            $processor($batch, $batchNumber);
        }
    }

    /**
     * Process dataset with custom batch size
     * 
     * @param array $dataset
     * @param int $batchSize
     * @param callable $processor Callback: fn(array $batch, int $batchNumber) => void
     */
    public function processWithSize(array $dataset, int $batchSize, callable $processor): void
    {
        $batches = array_chunk($dataset, $batchSize, true);

        foreach ($batches as $index => $batch) {
            $batchNumber = $index + 1;
            $processor($batch, $batchNumber);
        }
    }

    /**
     * Chunk dataset into batches
     * 
     * @return array[]
     */
    public function chunk(array $dataset): array
    {
        return array_chunk($dataset, $this->batchSize, true);
    }

    /**
     * Get recommended batch size for strategy
     */
    public static function getRecommendedBatchSize(ImportStrategy $strategy): int
    {
        return match($strategy) {
            ImportStrategy::STREAM => self::STREAM_BATCH_SIZE,
            ImportStrategy::TRANSACTIONAL => self::TRANSACTIONAL_BATCH_SIZE,
            ImportStrategy::BATCH => self::DEFAULT_BATCH_SIZE
        };
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    /**
     * Calculate total number of batches
     */
    public function getBatchCount(int $totalRows): int
    {
        return (int) ceil($totalRows / $this->batchSize);
    }

    /**
     * Get memory-efficient iterator for large datasets
     * 
     * @param array $dataset
     * @return \Generator
     */
    public function iterate(array $dataset): \Generator
    {
        $batches = $this->chunk($dataset);

        foreach ($batches as $index => $batch) {
            yield [
                'batch' => $batch,
                'batchNumber' => $index + 1,
                'totalBatches' => count($batches),
                'startRow' => array_key_first($batch),
                'endRow' => array_key_last($batch),
                'size' => count($batch)
            ];
        }
    }
}
