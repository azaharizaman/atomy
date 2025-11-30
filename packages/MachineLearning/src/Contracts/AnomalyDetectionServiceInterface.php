<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

/**
 * Anomaly detection service interface
 * 
 * Synchronous anomaly evaluation (<200ms SLA)
 */
interface AnomalyDetectionServiceInterface
{
    /**
     * Evaluate features for anomalies
     * 
     * @param string $processContext Context identifier (e.g., 'procurement_po_qty_check')
     * @param FeatureSetInterface $features Extracted features
     * @return AnomalyResultInterface Evaluation result
     * @throws \Nexus\Intelligence\Exceptions\FeatureVersionMismatchException
     * @throws \Nexus\Intelligence\Exceptions\QuotaExceededException
     * @throws \Nexus\Intelligence\Exceptions\AdversarialAttackDetectedException
     */
    public function evaluate(string $processContext, FeatureSetInterface $features): AnomalyResultInterface;
}
