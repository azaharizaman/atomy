<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DataProviders;

use Nexus\Party\Contracts\VendorQueryInterface;
use Nexus\ProcurementOperations\DTOs\VendorComplianceContext;
use Nexus\ProcurementOperations\Enums\VendorHoldReason;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Data provider for vendor compliance information.
 *
 * Aggregates vendor data from multiple sources into a single context DTO.
 */
final readonly class VendorComplianceDataProvider
{
    public function __construct(
        private VendorQueryInterface $vendorQuery,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Get compliance context for a vendor.
     */
    public function getContext(string $vendorId): ?VendorComplianceContext
    {
        $vendor = $this->vendorQuery->findById($vendorId);

        if ($vendor === null) {
            $this->logger->warning('Vendor not found for compliance check', [
                'vendor_id' => $vendorId,
            ]);
            return null;
        }

        // Extract active hold reasons from vendor
        $activeHoldReasons = $this->extractHoldReasons($vendor->getActiveHolds());

        // Build compliance checks map
        $complianceChecks = $this->buildComplianceChecks($vendor);

        return new VendorComplianceContext(
            vendorId: $vendor->getId(),
            vendorName: $vendor->getName(),
            isActive: $vendor->isActive(),
            isBlocked: !empty($activeHoldReasons),
            activeHoldReasons: $activeHoldReasons,
            isCompliant: $this->calculateOverallCompliance($complianceChecks),
            complianceChecks: $complianceChecks,
            lastComplianceReview: $vendor->getLastComplianceReviewDate(),
            performanceScore: $vendor->getPerformanceScore(),
            metadata: [
                'vendor_type' => $vendor->getType(),
                'payment_terms' => $vendor->getPaymentTerms(),
                'credit_limit' => $vendor->getCreditLimit(),
            ]
        );
    }

    /**
     * Get compliance contexts for multiple vendors.
     *
     * @param array<string> $vendorIds
     * @return array<string, VendorComplianceContext>
     */
    public function getContexts(array $vendorIds): array
    {
        $contexts = [];

        foreach ($vendorIds as $vendorId) {
            $context = $this->getContext($vendorId);
            if ($context !== null) {
                $contexts[$vendorId] = $context;
            }
        }

        return $contexts;
    }

    /**
     * Convert vendor hold data to VendorHoldReason enums.
     *
     * @param array<array{reason: string, ...}> $holds
     * @return array<VendorHoldReason>
     */
    private function extractHoldReasons(array $holds): array
    {
        $reasons = [];

        foreach ($holds as $hold) {
            $reason = VendorHoldReason::tryFrom($hold['reason'] ?? '');
            if ($reason !== null) {
                $reasons[] = $reason;
            }
        }

        return $reasons;
    }

    /**
     * Build compliance checks map from vendor data.
     *
     * @return array<string, bool>
     */
    private function buildComplianceChecks(mixed $vendor): array
    {
        return [
            'business_license_valid' => $vendor->hasValidBusinessLicense(),
            'insurance_current' => $vendor->hasCurrentInsurance(),
            'tax_documents_complete' => $vendor->hasTaxDocuments(),
            'bank_verified' => $vendor->isBankAccountVerified(),
            'contract_signed' => $vendor->hasSignedContract(),
            'w9_on_file' => $vendor->hasW9OnFile(),
        ];
    }

    /**
     * Calculate overall compliance from individual checks.
     *
     * @param array<string, bool> $checks
     */
    private function calculateOverallCompliance(array $checks): bool
    {
        // All required checks must pass for overall compliance
        $requiredChecks = [
            'business_license_valid',
            'tax_documents_complete',
        ];

        foreach ($requiredChecks as $checkName) {
            if (!($checks[$checkName] ?? false)) {
                return false;
            }
        }

        return true;
    }
}
