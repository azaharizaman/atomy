<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Services;

use DateTimeImmutable;
use Nexus\Intelligence\Contracts\FeatureExtractorInterface;
use Nexus\Intelligence\Contracts\FeatureSetInterface;
use Nexus\Intelligence\Contracts\UsageTrackingRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Instrumented feature extractor
 * 
 * Decorator pattern that wraps feature extractors to track performance metrics.
 * Records extraction duration, feature count, and resource consumption.
 */
final readonly class InstrumentedFeatureExtractor implements FeatureExtractorInterface
{
    public function __construct(
        private FeatureExtractorInterface $extractor,
        private UsageTrackingRepositoryInterface $usageTracking,
        private LoggerInterface $logger,
        private string $extractorName
    ) {}
    
    /**
     * {@inheritDoc}
     */
    public function extract(object $entity): FeatureSetInterface
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        try {
            // Execute actual extraction
            $featureSet = $this->extractor->extract($entity);
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            // Calculate metrics
            $durationMs = (int) (($endTime - $startTime) * 1000);
            $memoryUsedBytes = $endMemory - $startMemory;
            $featureCount = count($featureSet->toArray());
            
            // Track metrics asynchronously (non-blocking)
            $this->trackMetrics(
                $durationMs,
                $featureCount,
                $memoryUsedBytes,
                'success'
            );
            
            // Log slow extractions
            if ($durationMs > 200) {
                $this->logger->warning('Slow feature extraction detected', [
                    'extractor' => $this->extractorName,
                    'duration_ms' => $durationMs,
                    'feature_count' => $featureCount,
                    'entity_type' => get_class($entity),
                ]);
            }
            
            return $featureSet;
            
        } catch (\Throwable $e) {
            $endTime = microtime(true);
            $durationMs = (int) (($endTime - $startTime) * 1000);
            
            // Track failure
            $this->trackMetrics($durationMs, 0, 0, 'failed');
            
            $this->logger->error('Feature extraction failed', [
                'extractor' => $this->extractorName,
                'error' => $e->getMessage(),
                'entity_type' => get_class($entity),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * {@inheritDoc}
     */
    public function getFeatureKeys(): array
    {
        return $this->extractor->getFeatureKeys();
    }
    
    /**
     * {@inheritDoc}
     */
    public function getSchemaVersion(): string
    {
        return $this->extractor->getSchemaVersion();
    }
    
    /**
     * Track extraction metrics
     * 
     * @param int $durationMs Extraction duration in milliseconds
     * @param int $featureCount Number of features extracted
     * @param int $memoryBytes Memory used in bytes
     * @param string $status Extraction status (success/failed)
     * @return void
     */
    private function trackMetrics(
        int $durationMs,
        int $featureCount,
        int $memoryBytes,
        string $status
    ): void {
        try {
            $this->usageTracking->recordExtraction(
                $this->extractorName,
                $durationMs,
                $featureCount,
                $memoryBytes,
                $status,
                new DateTimeImmutable()
            );
        } catch (\Throwable $e) {
            // Don't fail extraction due to tracking errors
            $this->logger->warning('Failed to track extraction metrics', [
                'extractor' => $this->extractorName,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
