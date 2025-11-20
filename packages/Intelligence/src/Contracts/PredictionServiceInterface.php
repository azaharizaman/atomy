<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Contracts;

/**
 * Prediction service interface
 * 
 * Asynchronous prediction execution
 */
interface PredictionServiceInterface
{
    /**
     * Submit async prediction request
     * 
     * @param string $modelName Model identifier
     * @param array<string, mixed> $context Prediction context
     * @return string Job ID for tracking
     * @throws \Nexus\Intelligence\Exceptions\ModelNotFoundException
     * @throws \Nexus\Intelligence\Exceptions\QuotaExceededException
     */
    public function predictAsync(string $modelName, array $context): string;

    /**
     * Get prediction result by job ID
     * 
     * @param string $jobId Job identifier
     * @return PredictionResultInterface|null Null if not ready
     */
    public function getPrediction(string $jobId): ?PredictionResultInterface;

    /**
     * Check prediction status
     * 
     * @param string $jobId Job identifier
     * @return string Status (pending, processing, completed, failed)
     */
    public function getStatus(string $jobId): string;
}
