<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

use DateTimeImmutable;

/**
 * Usage tracking repository interface
 * 
 * Tracks extractor performance metrics for cost analysis and optimization.
 */
interface UsageTrackingRepositoryInterface
{
    /**
     * Record a feature extraction operation
     * 
     * @param string $extractorName Fully qualified extractor class name
     * @param int $durationMs Extraction duration in milliseconds
     * @param int $featureCount Number of features extracted
     * @param int $memoryBytes Memory consumed in bytes
     * @param string $status Operation status (success/failed)
     * @param DateTimeImmutable $timestamp When extraction occurred
     * @return void
     */
    public function recordExtraction(
        string $extractorName,
        int $durationMs,
        int $featureCount,
        int $memoryBytes,
        string $status,
        DateTimeImmutable $timestamp
    ): void;
}
