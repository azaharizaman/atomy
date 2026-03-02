<?php

declare(strict_types=1);

namespace Nexus\ESGOperations\Contracts;

/**
 * Interface for AI-driven ESG data extraction from unstructured documents.
 */
interface ESGExtractionServiceInterface
{
    /**
     * Extract sustainability metrics from a document (e.g., energy bill, permit).
     * 
     * @param string $documentId Reference to the document in Nexus\Storage
     * @return array<array{metric_id: string, value: float, unit: string, confidence: float}>
     */
    public function extractMetrics(string $documentId): array;

    /**
     * Validate environmental claims or permits against regulatory thresholds.
     */
    public function validateClaims(string $documentId, array $thresholds): array;
}
