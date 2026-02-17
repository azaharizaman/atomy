<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

/**
 * Interface for procurement audit coordination.
 */
interface ProcurementAuditCoordinatorInterface
{
    /**
     * Generate a comprehensive audit evidence package.
     */
    public function generateEvidencePackage(string $tenantId, string $period): array;

    /**
     * Perform a segregation of duties (SoD) check across procurement roles.
     */
    public function performSodAudit(string $tenantId): array;

    /**
     * Validate all approval levels for compliance with corporate policy.
     */
    public function validateApprovalCompliance(string $tenantId, string $period): array;
}
