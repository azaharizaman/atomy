<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

/**
 * Feature extractor interface
 * 
 * Domain packages implement this to transform entities into standardized features.
 */
interface FeatureExtractorInterface
{
    /**
     * Extract features from domain entity
     * 
     * @param object $entity Domain entity (PurchaseOrderLine, Invoice, etc.)
     * @return FeatureSetInterface Standardized feature set
     */
    public function extract(object $entity): FeatureSetInterface;

    /**
     * Get list of feature keys this extractor produces
     * 
     * @return array<string> Feature names
     */
    public function getFeatureKeys(): array;

    /**
     * Get schema version this extractor produces
     * 
     * @return string Semantic version
     */
    public function getSchemaVersion(): string;
}
