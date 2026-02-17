<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

/**
 * Interface for vendor risk assessment coordination.
 */
interface VendorRiskAssessmentCoordinatorInterface
{
    /**
     * Perform a comprehensive risk assessment for a vendor.
     */
    public function assessVendorRisk(string $tenantId, string $vendorId): array;

    /**
     * Get periodic risk review report for all active vendors.
     */
    public function getRiskReviewReport(string $tenantId): array;
}
