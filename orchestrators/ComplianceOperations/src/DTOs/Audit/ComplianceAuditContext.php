<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DTOs\Audit;

/**
 * Context DTO for compliance audit data.
 *
 * Aggregates all audit-related data for compliance workflows.
 */
final readonly class ComplianceAuditContext
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $entityType Entity type
     * @param string $entityId Entity identifier
     * @param array<int, array<string, mixed>> $auditTrail Audit trail records
     * @param bool $integrityVerified Whether audit chain integrity is verified
     * @param array<string, mixed> $statistics Audit statistics
     * @param \DateTimeImmutable $fetchedAt Fetch timestamp
     */
    public function __construct(
        public string $tenantId,
        public string $entityType,
        public string $entityId,
        public array $auditTrail,
        public bool $integrityVerified,
        public array $statistics,
        public \DateTimeImmutable $fetchedAt,
    ) {}

    /**
     * Get record count.
     */
    public function getRecordCount(): int
    {
        return count($this->auditTrail);
    }

    /**
     * Get last activity timestamp.
     */
    public function getLastActivity(): ?string
    {
        if (empty($this->auditTrail)) {
            return null;
        }
        return $this->auditTrail[0]['timestamp'] ?? null;
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'entityType' => $this->entityType,
            'entityId' => $this->entityId,
            'auditTrail' => $this->auditTrail,
            'integrityVerified' => $this->integrityVerified,
            'statistics' => $this->statistics,
            'recordCount' => $this->getRecordCount(),
            'fetchedAt' => $this->fetchedAt->format('Y-m-d H:i:s'),
        ];
    }
}
