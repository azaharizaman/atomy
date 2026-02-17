<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\ProcurementOperations\Contracts\ProcurementAuditCoordinatorInterface;
use Nexus\ProcurementOperations\Contracts\ProcurementAuditServiceInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for procurement audit operations.
 */
class ProcurementAuditCoordinator implements ProcurementAuditCoordinatorInterface
{
    public function __construct(
        private readonly ProcurementAuditServiceInterface $auditService,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * {@inheritdoc}
     */
    public function generateEvidencePackage(string $tenantId, string $period): array
    {
        $this->logger->info('Coordinating audit evidence package generation', [
            'tenant_id' => $tenantId,
            'period' => $period,
        ]);

        return $this->auditService->generateSox404Evidence($tenantId, $period);
    }

    /**
     * {@inheritdoc}
     */
    public function performSodAudit(string $tenantId): array
    {
        $this->logger->info('Coordinating Segregation of Duties audit', [
            'tenant_id' => $tenantId,
        ]);

        return $this->auditService->getSegregationOfDutiesReport($tenantId, new \DateTimeImmutable());
    }

    /**
     * {@inheritdoc}
     */
    public function validateApprovalCompliance(string $tenantId, string $period): array
    {
        $this->logger->info('Coordinating approval authority compliance validation', [
            'tenant_id' => $tenantId,
            'period' => $period,
        ]);

        // Assuming period is year-quarter '2024-Q4'
        $fiscalYear = (int) substr($period, 0, 4);
        $quarter = (int) substr($period, -1);
        
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $quarter * 3;
        
        $periodStart = new \DateTimeImmutable("{$fiscalYear}-{$startMonth}-01");
        $periodEnd = (new \DateTimeImmutable("{$fiscalYear}-{$endMonth}-01"))->modify('last day of this month');

        return $this->auditService->validateApprovalAuthority($tenantId, $periodStart, $periodEnd);
    }
}
