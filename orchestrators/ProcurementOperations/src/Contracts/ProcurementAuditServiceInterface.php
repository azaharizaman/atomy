<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

/**
 * Contract for procurement audit service.
 *
 * Generates SOX 404 evidence and compliance documentation for:
 * - Internal Control Testing (Section 404)
 * - Segregation of Duties Reports
 * - Approval Authority Validation
 * - Exception Tracking and Resolution
 */
interface ProcurementAuditServiceInterface
{
    /**
     * Generate SOX 404 evidence package.
     *
     * @param string $tenantId Tenant context
     * @param string $auditPeriod Audit period identifier (e.g., '2024-Q4')
     * @param array $controlAreas Control areas to include
     * @return array Evidence package with supporting documentation
     */
    public function generateSox404Evidence(
        string $tenantId,
        string $auditPeriod,
        array $controlAreas = [],
    ): array;

    /**
     * Get segregation of duties report.
     *
     * @param string $tenantId Tenant context
     * @param \DateTimeImmutable $asOfDate Point-in-time for report
     * @return array SoD compliance status by user/role
     */
    public function getSegregationOfDutiesReport(
        string $tenantId,
        \DateTimeImmutable $asOfDate,
    ): array;

    /**
     * Validate approval authority compliance.
     *
     * @param string $tenantId Tenant context
     * @param \DateTimeImmutable $periodStart Period start date
     * @param \DateTimeImmutable $periodEnd Period end date
     * @return array Approval authority validation results
     */
    public function validateApprovalAuthority(
        string $tenantId,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
    ): array;

    /**
     * Get audit exceptions and resolutions.
     *
     * @param string $tenantId Tenant context
     * @param string $auditPeriod Audit period identifier
     * @param array $filters Optional filters (status, severity, control_area)
     * @return array Exceptions with resolution status
     */
    public function getAuditExceptions(
        string $tenantId,
        string $auditPeriod,
        array $filters = [],
    ): array;

    /**
     * Record audit finding.
     *
     * @param string $tenantId Tenant context
     * @param string $controlArea Affected control area
     * @param string $findingType Type of finding (DEFICIENCY, SIGNIFICANT_DEFICIENCY, MATERIAL_WEAKNESS)
     * @param string $description Finding description
     * @param string $recordedBy Auditor recording finding
     * @return array Finding record details
     */
    public function recordAuditFinding(
        string $tenantId,
        string $controlArea,
        string $findingType,
        string $description,
        string $recordedBy,
    ): array;

    /**
     * Resolve audit finding.
     *
     * @param string $findingId Finding identifier
     * @param string $resolution Resolution description
     * @param string $resolvedBy User resolving finding
     * @param array $supportingDocuments Supporting documentation references
     * @return array Resolution confirmation
     */
    public function resolveAuditFinding(
        string $findingId,
        string $resolution,
        string $resolvedBy,
        array $supportingDocuments = [],
    ): array;

    /**
     * Get internal control test results.
     *
     * @param string $tenantId Tenant context
     * @param string $controlId Control identifier
     * @param string $testPeriod Test period
     * @return array Test results with sample data
     */
    public function getControlTestResults(
        string $tenantId,
        string $controlId,
        string $testPeriod,
    ): array;

    /**
     * Generate management representation letter data.
     *
     * @param string $tenantId Tenant context
     * @param string $auditPeriod Audit period
     * @return array Data for management representation letter
     */
    public function getManagementRepresentationData(
        string $tenantId,
        string $auditPeriod,
    ): array;

    /**
     * Get three-way match audit trail.
     *
     * @param string $tenantId Tenant context
     * @param \DateTimeImmutable $periodStart Period start
     * @param \DateTimeImmutable $periodEnd Period end
     * @param array $filters Optional filters
     * @return array Three-way match compliance data
     */
    public function getThreeWayMatchAuditTrail(
        string $tenantId,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
        array $filters = [],
    ): array;
}
