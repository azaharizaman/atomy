<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DTOs\Privacy;

/**
 * Summary DTO for privacy dashboard data.
 */
final readonly class PrivacySummaryData
{
    /**
     * @param string $tenantId Tenant identifier
     * @param int $totalRequests Total number of DSR requests
     * @param int $pendingRequests Number of pending requests
     * @param int $overdueRequests Number of overdue requests
     * @param int $activeBreaches Number of active breaches
     * @param int $expiringConsents Number of expiring consents
     * @param float $averageCompletionDays Average completion time in days
     * @param \DateTimeImmutable $generatedAt Generation timestamp
     */
    public function __construct(
        public string $tenantId,
        public int $totalRequests,
        public int $pendingRequests,
        public int $overdueRequests,
        public int $activeBreaches,
        public int $expiringConsents,
        public float $averageCompletionDays,
        public \DateTimeImmutable $generatedAt,
    ) {}

    /**
     * Get compliance rate (completed / total).
     */
    public function getComplianceRate(): float
    {
        if ($this->totalRequests === 0) {
            return 100.0;
        }
        $completed = $this->totalRequests - $this->pendingRequests;
        return round(($completed / $this->totalRequests) * 100, 2);
    }

    /**
     * Check if there are critical issues.
     */
    public function hasCriticalIssues(): bool
    {
        return $this->activeBreaches > 0 || $this->overdueRequests > 0;
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
            'totalRequests' => $this->totalRequests,
            'pendingRequests' => $this->pendingRequests,
            'overdueRequests' => $this->overdueRequests,
            'activeBreaches' => $this->activeBreaches,
            'expiringConsents' => $this->expiringConsents,
            'averageCompletionDays' => $this->averageCompletionDays,
            'complianceRate' => $this->getComplianceRate(),
            'hasCriticalIssues' => $this->hasCriticalIssues(),
            'generatedAt' => $this->generatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
