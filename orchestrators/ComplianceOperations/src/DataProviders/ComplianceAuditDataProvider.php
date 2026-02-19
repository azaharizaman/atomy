<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DataProviders;

use Nexus\Audit\Contracts\AuditEngineInterface;
use Nexus\Audit\Contracts\AuditStorageInterface;
use Nexus\Audit\Contracts\AuditVerifierInterface;
use Nexus\ComplianceOperations\DTOs\Audit\ComplianceAuditContext;
use Nexus\ComplianceOperations\DTOs\Audit\AuditSummaryData;
use Nexus\ComplianceOperations\Exceptions\AuditDataException;
use Psr\Log\LoggerInterface;

/**
 * DataProvider for audit trail data for compliance operations.
 *
 * Aggregates audit data from the Audit package to provide
 * comprehensive context for compliance workflows including:
 * - Audit trail retrieval
 * - Compliance evidence collection
 * - Audit verification
 * - Regulatory reporting data
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * DataProviders abstract data fetching from Coordinators.
 */
final readonly class ComplianceAuditDataProvider
{
    public function __construct(
        private AuditEngineInterface $auditEngine,
        private AuditStorageInterface $auditStorage,
        private AuditVerifierInterface $auditVerifier,
        private LoggerInterface $logger,
    ) {}

    /**
     * Get comprehensive audit context for an entity.
     *
     * @param string $tenantId Tenant context
     * @param string $entityType Entity type (e.g., 'party', 'transaction')
     * @param string $entityId Entity identifier
     * @throws AuditDataException If data cannot be retrieved
     */
    public function getAuditContext(
        string $tenantId,
        string $entityType,
        string $entityId
    ): ComplianceAuditContext {
        $this->logger->info('Fetching audit context', [
            'tenant_id' => $tenantId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);

        // Get audit trail for entity using findBySubject from AuditStorageInterface
        $auditTrail = $this->auditStorage->findBySubject($tenantId, $entityType, $entityId);

        // Verify audit chain integrity for tenant using HashChainVerifier::getIntegrityReport()
        $integrityReport = $this->auditVerifier->getIntegrityReport($tenantId);
        $integrityVerified = $integrityReport['is_valid'] ?? false;

        // Use AuditEngine::getStatistics() for detailed statistics
        $periodStart = new \DateTimeImmutable('-30 days');
        $periodEnd = new \DateTimeImmutable();
        $statistics = $this->auditEngine->getStatistics($tenantId, $periodStart, $periodEnd);

        return new ComplianceAuditContext(
            tenantId: $tenantId,
            entityType: $entityType,
            entityId: $entityId,
            auditTrail: $this->buildAuditTrailData($auditTrail),
            integrityVerified: $integrityVerified,
            statistics: $statistics,
            fetchedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Get audit summary for dashboard display.
     *
     * @param string $tenantId Tenant context
     * @param \DateTimeImmutable|null $startDate Period start
     * @param \DateTimeImmutable|null $endDate Period end
     */
    public function getAuditSummary(
        string $tenantId,
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $endDate = null
    ): AuditSummaryData {
        $this->logger->info('Fetching audit summary', [
            'tenant_id' => $tenantId,
            'start_date' => $startDate?->format('Y-m-d'),
            'end_date' => $endDate?->format('Y-m-d'),
        ]);

        try {
            $fromDate = $startDate ?? new \DateTimeImmutable('-30 days');
            $toDate = $endDate ?? new \DateTimeImmutable();

            // Use AuditEngine::getStatistics() for tenant-specific statistics
            $statistics = $this->auditEngine->getStatistics($tenantId, $fromDate, $toDate);

            // Use HashChainVerifier::getIntegrityReport() for verification data
            $integrityReport = $this->auditVerifier->getIntegrityReport($tenantId);

            return new AuditSummaryData(
                tenantId: $tenantId,
                totalRecords: $statistics['total_records'] ?? 0,
                verifiedRecords: $integrityReport['verified_records'] ?? 0,
                failedVerifications: $integrityReport['failed_count'] ?? 0,
                recordsByType: $statistics['by_record_type'] ?? [],
                periodStart: $fromDate,
                periodEnd: $toDate,
                generatedAt: new \DateTimeImmutable(),
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch audit summary', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw new AuditDataException('Failed to fetch audit summary: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get audit trail for a specific entity.
     *
     * @param string $tenantId Tenant ID
     * @param string $entityType Entity type
     * @param string $entityId Entity identifier
     * @return array<int, array<string, mixed>>
     */
    public function getAuditTrail(string $tenantId, string $entityType, string $entityId): array
    {
        $this->logger->info('Fetching audit trail', [
            'tenant_id' => $tenantId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);

        // Use findBySubject from AuditStorageInterface
        $trail = $this->auditStorage->findBySubject($tenantId, $entityType, $entityId);
        return $this->buildAuditTrailData($trail);
    }

    /**
     * Verify audit chain integrity.
     *
     * @param string $tenantId Tenant ID
     */
    public function verifyIntegrity(string $tenantId): bool
    {
        $this->logger->info('Verifying audit integrity', [
            'tenant_id' => $tenantId,
        ]);

        // Use verifyChainIntegrity from AuditVerifierInterface
        return $this->auditVerifier->verifyChainIntegrity($tenantId);
    }

    /**
     * Get compliance evidence for regulatory reporting.
     *
     * @param string $tenantId Tenant context
     * @param string $reportType Report type (e.g., 'SOX', 'GDPR', 'AML')
     * @param \DateTimeImmutable $startDate Period start
     * @param \DateTimeImmutable $endDate Period end
     * @return array<string, mixed>
     */
    public function getComplianceEvidence(
        string $tenantId,
        string $reportType,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        $this->logger->info('Fetching compliance evidence', [
            'tenant_id' => $tenantId,
            'report_type' => $reportType,
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d'),
        ]);

        try {
            // Use AuditEngine::getStatistics() for period statistics
            $statistics = $this->auditEngine->getStatistics($tenantId, $startDate, $endDate);

            // Use HashChainVerifier::getIntegrityReport() for integrity data
            $integrityReport = $this->auditVerifier->getIntegrityReport($tenantId);

            // Calculate integrity score
            $totalRecords = $statistics['total_records'] ?? 0;
            $verifiedRecords = $integrityReport['verified_records'] ?? 0;
            $integrityScore = $totalRecords > 0 ? round(($verifiedRecords / $totalRecords) * 100, 2) : 100;

            return [
                'reportType' => $reportType,
                'periodStart' => $startDate->format('Y-m-d'),
                'periodEnd' => $endDate->format('Y-m-d'),
                'totalRecords' => $totalRecords,
                'verifiedRecords' => $verifiedRecords,
                'integrityScore' => $integrityScore,
                'failedVerifications' => $integrityReport['failed_count'] ?? 0,
                'recordsByType' => $statistics['by_record_type'] ?? [],
                'generatedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch compliance evidence', [
                'tenant_id' => $tenantId,
                'report_type' => $reportType,
                'error' => $e->getMessage(),
            ]);

            throw new AuditDataException('Failed to fetch compliance evidence: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Search audit records.
     *
     * @param string $tenantId Tenant context
     * @param array<string, mixed> $criteria Search criteria
     * @param int $limit Maximum results
     * @return array<int, array<string, mixed>>
     */
    public function searchAuditRecords(
        string $tenantId,
        array $criteria,
        int $limit = 100
    ): array {
        $this->logger->info('Searching audit records', [
            'tenant_id' => $tenantId,
            'criteria' => $criteria,
        ]);

        // Use AuditStorage::search() for searching audit records
        try {
            $searchCriteria = array_merge($criteria, [
                'tenant_id' => $tenantId,
                'limit' => $limit,
            ]);
            
            $records = $this->auditStorage->search($searchCriteria);
            return $this->buildAuditTrailData($records);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to search audit records', [
                'tenant_id' => $tenantId,
                'criteria' => $criteria,
                'error' => $e->getMessage(),
            ]);

            throw new AuditDataException('Failed to search audit records: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Build audit trail data array.
     *
     * @param array $records
     * @return array<int, array<string, mixed>>
     */
    private function buildAuditTrailData(array $records): array
    {
        $trail = [];
        foreach ($records as $record) {
            $trail[] = [
                'recordId' => $record->getId(),
                'action' => $record->getRecordType(),
                'entityType' => $record->getSubjectType(),
                'entityId' => $record->getSubjectId(),
                'userId' => $record->getCauserId(),
                'timestamp' => $record->getCreatedAt()->format('Y-m-d H:i:s'),
                'ipAddress' => $record->getProperties()['ip_address'] ?? null,
                'changes' => $record->getProperties(),
                'metadata' => $record->getMetadata(),
            ];
        }
        return $trail;
    }
}
