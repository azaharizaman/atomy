<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DTOs\Audit;

/**
 * Summary DTO for audit dashboard data.
 */
final readonly class AuditSummaryData
{
    /**
     * @param string $tenantId Tenant identifier
     * @param int $totalRecords Total number of audit records
     * @param int $verifiedRecords Number of verified records
     * @param int $failedVerifications Number of failed verifications
     * @param array<string, int> $recordsByType Records grouped by type
     * @param \DateTimeImmutable $periodStart Period start
     * @param \DateTimeImmutable $periodEnd Period end
     * @param \DateTimeImmutable $generatedAt Generation timestamp
     */
    public function __construct(
        public string $tenantId,
        public int $totalRecords,
        public int $verifiedRecords,
        public int $failedVerifications,
        public array $recordsByType,
        public \DateTimeImmutable $periodStart,
        public \DateTimeImmutable $periodEnd,
        public \DateTimeImmutable $generatedAt,
    ) {}

    /**
     * Get integrity score (0-100).
     */
    public function getIntegrityScore(): float
    {
        if ($this->totalRecords === 0) {
            return 100.0;
        }
        $verified = $this->totalRecords - $this->failedVerifications;
        return round(($verified / $this->totalRecords) * 100, 2);
    }

    /**
     * Check if there are integrity issues.
     */
    public function hasIntegrityIssues(): bool
    {
        return $this->failedVerifications > 0;
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
            'totalRecords' => $this->totalRecords,
            'verifiedRecords' => $this->verifiedRecords,
            'failedVerifications' => $this->failedVerifications,
            'recordsByType' => $this->recordsByType,
            'integrityScore' => $this->getIntegrityScore(),
            'hasIntegrityIssues' => $this->hasIntegrityIssues(),
            'periodStart' => $this->periodStart->format('Y-m-d'),
            'periodEnd' => $this->periodEnd->format('Y-m-d'),
            'generatedAt' => $this->generatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
