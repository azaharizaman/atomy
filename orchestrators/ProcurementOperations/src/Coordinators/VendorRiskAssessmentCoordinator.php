<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\AmlCompliance\Contracts\AmlRiskAssessorInterface;
use Nexus\Payable\Contracts\VendorRepositoryInterface;
use Nexus\ProcurementOperations\Adapters\VendorToAmlPartyAdapter;
use Nexus\ProcurementOperations\Contracts\VendorRiskAssessmentCoordinatorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for vendor risk assessment operations.
 */
class VendorRiskAssessmentCoordinator implements VendorRiskAssessmentCoordinatorInterface
{
    public function __construct(
        private readonly VendorRepositoryInterface $vendorRepository,
        private readonly AmlRiskAssessorInterface $riskAssessor,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * {@inheritdoc}
     */
    public function assessVendorRisk(string $tenantId, string $vendorId): array
    {
        $this->logger->info('Coordinating vendor risk assessment', [
            'vendor_id' => $vendorId,
            'tenant_id' => $tenantId,
        ]);

        $vendor = $this->vendorRepository->findById($vendorId);
        if (!$vendor) {
            throw new \RuntimeException("Vendor not found: {$vendorId}");
        }

        $partyAdapter = new VendorToAmlPartyAdapter($vendor);
        $riskScore = $this->riskAssessor->performRiskAssessment($partyAdapter);

        return [
            'vendor_id' => $vendorId,
            'vendor_name' => $vendor->getName(),
            'risk_score' => $riskScore->getScore(),
            'risk_level' => $riskScore->getLevel()->value,
            'recommendations' => $this->riskAssessor->generateRecommendations($riskScore),
            'assessment_date' => (new \DateTimeImmutable())->format('c'),
            'next_review_date' => $this->riskAssessor->calculateNextReviewDate($riskScore->getLevel())->format('c'),
            'requires_edd' => $this->riskAssessor->requiresEdd($riskScore),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRiskReviewReport(string $tenantId): array
    {
        $this->logger->info('Generating periodic risk review report', [
            'tenant_id' => $tenantId,
        ]);

        $vendors = $this->vendorRepository->getAll($tenantId, ['status' => 'active']);
        $results = [];

        foreach ($vendors as $vendor) {
            try {
                $partyAdapter = new VendorToAmlPartyAdapter($vendor);
                $riskScore = $this->riskAssessor->performRiskAssessment($partyAdapter);
                
                $results[] = [
                    'vendor_id' => $vendor->getId(),
                    'vendor_name' => $vendor->getName(),
                    'risk_score' => $riskScore->getScore(),
                    'risk_level' => $riskScore->getLevel()->value,
                ];
            } catch (\Exception $e) {
                $this->logger->error("Failed to assess risk for vendor {$vendor->getId()}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'tenant_id' => $tenantId,
            'total_vendors' => count($vendors),
            'assessed_count' => count($results),
            'report_date' => (new \DateTimeImmutable())->format('c'),
            'summary' => $results,
        ];
    }
}
