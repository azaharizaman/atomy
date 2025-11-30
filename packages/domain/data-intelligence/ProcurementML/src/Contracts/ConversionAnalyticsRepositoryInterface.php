<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Contracts;

/**
 * Conversion analytics repository interface for efficiency prediction
 * 
 * Provides metrics for requisition-to-PO conversion analysis,
 * resource capacity tracking, and bottleneck identification.
 */
interface ConversionAnalyticsRepositoryInterface
{
    /**
     * Check if vendor is in approved catalog
     * 
     * @param string $vendorId Vendor identifier
     * @return bool True if vendor has catalog integration
     */
    public function isVendorInCatalog(string $vendorId): bool;

    /**
     * Get catalog coverage score for requisition
     * Percentage of requisition items available in vendor's catalog
     * 
     * @param string $vendorId Vendor identifier
     * @param object $requisition Requisition object
     * @return float Coverage score (0.0 to 1.0)
     */
    public function getCatalogCoverageScore(string $vendorId, object $requisition): float;

    /**
     * Get category's average conversion time
     * 
     * @param string $categoryId Product category identifier
     * @return float Average days from requisition approval to PO creation
     */
    public function getCategoryAvgConversionTime(string $categoryId): float;

    /**
     * Get vendor's average conversion time
     * 
     * @param string $vendorId Vendor identifier
     * @return float Average days for this vendor's requisitions
     */
    public function getVendorAvgConversionTime(string $vendorId): float;

    /**
     * Get procurement officer's current workload
     * 
     * @param string $officerId Officer identifier
     * @return int Number of active requisitions being processed
     */
    public function getProcurementOfficerWorkload(string $officerId): int;

    /**
     * Get officer's average processing time
     * 
     * @param string $officerId Officer identifier
     * @return float Average days to convert requisitions
     */
    public function getOfficerAvgProcessingTime(string $officerId): float;

    /**
     * Check if vendor is marked as preferred
     * 
     * @param string $vendorId Vendor identifier
     * @return bool True if preferred vendor
     */
    public function isPreferredVendor(string $vendorId): bool;
}
