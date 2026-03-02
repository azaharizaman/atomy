<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Interface for semantic mapping of extracted line items to taxonomies.
 */
interface SemanticMapperInterface
{
    /**
     * Map a raw vendor description to a standard taxonomy code (UNSPSC).
     * 
     * @param string $description The raw vendor product description
     * @param string $tenantId For tenant-specific taxonomy configurations
     * 
     * @return array{code: string, confidence: float, version: string}
     */
    public function mapToTaxonomy(string $description, string $tenantId): array;

    /**
     * Validate that a taxonomy code exists in the system.
     */
    public function validateCode(string $code, string $version): bool;
}
