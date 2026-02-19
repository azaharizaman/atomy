<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DTOs\Aml;

/**
 * Summary DTO for AML dashboard data.
 */
final readonly class AmlSummaryData
{
    /**
     * @param string $tenantId Tenant identifier
     * @param int $totalAssessments Total number of assessments
     * @param int $highRiskCount Number of high-risk parties
     * @param int $pendingSars Number of pending SARs
     * @param int $filedSars Number of filed SARs
     * @param int $alertsGenerated Number of alerts generated
     * @param \DateTimeImmutable $generatedAt Generation timestamp
     */
    public function __construct(
        public string $tenantId,
        public int $totalAssessments,
        public int $highRiskCount,
        public int $pendingSars,
        public int $filedSars,
        public int $alertsGenerated,
        public \DateTimeImmutable $generatedAt,
    ) {}

    /**
     * Get SAR filing rate.
     */
    public function getSarFilingRate(): float
    {
        $total = $this->pendingSars + $this->filedSars;
        if ($total === 0) {
            return 0.0;
        }
        return round($this->filedSars / $total * 100, 2);
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
            'totalAssessments' => $this->totalAssessments,
            'highRiskCount' => $this->highRiskCount,
            'pendingSars' => $this->pendingSars,
            'filedSars' => $this->filedSars,
            'alertsGenerated' => $this->alertsGenerated,
            'sarFilingRate' => $this->getSarFilingRate(),
            'generatedAt' => $this->generatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
