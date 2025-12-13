<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Audit;

/**
 * DTO representing a complete SOX 404 evidence package.
 */
final readonly class Sox404EvidenceData
{
    /**
     * @param string $evidenceId Evidence package identifier
     * @param string $tenantId Tenant context
     * @param int $fiscalYear Fiscal year covered
     * @param \DateTimeImmutable $periodStart Period start date
     * @param \DateTimeImmutable $periodEnd Period end date
     * @param \DateTimeImmutable $generatedAt When evidence was generated
     * @param string $generatedBy User who generated evidence
     * @param array<ControlTestResultData> $controlTestResults Control test results
     * @param array<AuditFindingData> $findings Audit findings
     * @param array $segregationOfDutiesMatrix SoD compliance matrix
     * @param array $approvalAuthorityMatrix Approval authority matrix
     * @param array $threeWayMatchAuditTrail 3-way match audit trail
     * @param array $managementAssertions Management assertions
     * @param array $controlEnvironmentSummary Control environment summary
     * @param string|null $overallConclusion Overall audit conclusion
     * @param array $metadata Additional evidence metadata
     */
    public function __construct(
        public string $evidenceId,
        public string $tenantId,
        public int $fiscalYear,
        public \DateTimeImmutable $periodStart,
        public \DateTimeImmutable $periodEnd,
        public \DateTimeImmutable $generatedAt,
        public string $generatedBy,
        public array $controlTestResults = [],
        public array $findings = [],
        public array $segregationOfDutiesMatrix = [],
        public array $approvalAuthorityMatrix = [],
        public array $threeWayMatchAuditTrail = [],
        public array $managementAssertions = [],
        public array $controlEnvironmentSummary = [],
        public ?string $overallConclusion = null,
        public array $metadata = [],
    ) {}

    /**
     * Get count of control tests.
     */
    public function getControlTestCount(): int
    {
        return count($this->controlTestResults);
    }

    /**
     * Get count of passing control tests.
     */
    public function getPassingControlTestCount(): int
    {
        return count(array_filter(
            $this->controlTestResults,
            fn(ControlTestResultData $test) => $test->isPassing,
        ));
    }

    /**
     * Get control test pass rate.
     */
    public function getControlTestPassRate(): float
    {
        $total = $this->getControlTestCount();
        if ($total === 0) {
            return 0.0;
        }

        return round(($this->getPassingControlTestCount() / $total) * 100, 2);
    }

    /**
     * Get count of total findings.
     */
    public function getFindingCount(): int
    {
        return count($this->findings);
    }

    /**
     * Get count of open findings.
     */
    public function getOpenFindingCount(): int
    {
        return count(array_filter(
            $this->findings,
            fn(AuditFindingData $finding) => $finding->isOpen(),
        ));
    }

    /**
     * Get count of material weaknesses.
     */
    public function getMaterialWeaknessCount(): int
    {
        return count(array_filter(
            $this->findings,
            fn(AuditFindingData $finding) =>
                $finding->severity->value === 'MATERIAL_WEAKNESS' && $finding->isOpen(),
        ));
    }

    /**
     * Get count of significant deficiencies.
     */
    public function getSignificantDeficiencyCount(): int
    {
        return count(array_filter(
            $this->findings,
            fn(AuditFindingData $finding) =>
                $finding->severity->value === 'SIGNIFICANT_DEFICIENCY' && $finding->isOpen(),
        ));
    }

    /**
     * Check if internal controls are effective (no material weaknesses).
     */
    public function areControlsEffective(): bool
    {
        return $this->getMaterialWeaknessCount() === 0;
    }

    /**
     * Get audit opinion type.
     */
    public function getAuditOpinionType(): string
    {
        $materialWeaknesses = $this->getMaterialWeaknessCount();

        if ($materialWeaknesses === 0) {
            return 'UNQUALIFIED';
        }

        if ($materialWeaknesses <= 2) {
            return 'QUALIFIED';
        }

        return 'ADVERSE';
    }

    /**
     * Get findings grouped by severity.
     *
     * @return array<string, array<AuditFindingData>>
     */
    public function getFindingsBySeverity(): array
    {
        $grouped = [];
        foreach ($this->findings as $finding) {
            $severity = $finding->severity->value;
            if (!isset($grouped[$severity])) {
                $grouped[$severity] = [];
            }
            $grouped[$severity][] = $finding;
        }
        return $grouped;
    }

    /**
     * Get control test results grouped by COSO component.
     *
     * @return array<string, array<ControlTestResultData>>
     */
    public function getControlTestsByCOSO(): array
    {
        $grouped = [];
        foreach ($this->controlTestResults as $test) {
            $coso = $test->getCOSOComponent();
            if (!isset($grouped[$coso])) {
                $grouped[$coso] = [];
            }
            $grouped[$coso][] = $test;
        }
        return $grouped;
    }

    /**
     * Get overall exception rate across all control tests.
     */
    public function getOverallExceptionRate(): float
    {
        $totalSamples = 0;
        $totalExceptions = 0;

        foreach ($this->controlTestResults as $test) {
            $totalSamples += $test->sampleSize;
            $totalExceptions += $test->exceptionsFound;
        }

        if ($totalSamples === 0) {
            return 0.0;
        }

        return round(($totalExceptions / $totalSamples) * 100, 2);
    }

    /**
     * Get executive summary.
     */
    public function getExecutiveSummary(): array
    {
        return [
            'evidence_id' => $this->evidenceId,
            'fiscal_year' => $this->fiscalYear,
            'period_covered' => sprintf(
                '%s to %s',
                $this->periodStart->format('Y-m-d'),
                $this->periodEnd->format('Y-m-d'),
            ),
            'generated_at' => $this->generatedAt->format('Y-m-d H:i:s'),
            'control_tests' => [
                'total' => $this->getControlTestCount(),
                'passing' => $this->getPassingControlTestCount(),
                'pass_rate' => $this->getControlTestPassRate() . '%',
            ],
            'findings' => [
                'total' => $this->getFindingCount(),
                'open' => $this->getOpenFindingCount(),
                'material_weaknesses' => $this->getMaterialWeaknessCount(),
                'significant_deficiencies' => $this->getSignificantDeficiencyCount(),
            ],
            'overall_exception_rate' => $this->getOverallExceptionRate() . '%',
            'controls_effective' => $this->areControlsEffective(),
            'audit_opinion' => $this->getAuditOpinionType(),
            'conclusion' => $this->overallConclusion,
        ];
    }

    /**
     * Check if evidence package is complete.
     */
    public function isComplete(): bool
    {
        return !empty($this->controlTestResults)
            && !empty($this->segregationOfDutiesMatrix)
            && !empty($this->approvalAuthorityMatrix)
            && !empty($this->managementAssertions)
            && $this->overallConclusion !== null;
    }

    /**
     * Get missing evidence components.
     */
    public function getMissingComponents(): array
    {
        $missing = [];

        if (empty($this->controlTestResults)) {
            $missing[] = 'control_test_results';
        }

        if (empty($this->segregationOfDutiesMatrix)) {
            $missing[] = 'segregation_of_duties_matrix';
        }

        if (empty($this->approvalAuthorityMatrix)) {
            $missing[] = 'approval_authority_matrix';
        }

        if (empty($this->managementAssertions)) {
            $missing[] = 'management_assertions';
        }

        if ($this->overallConclusion === null) {
            $missing[] = 'overall_conclusion';
        }

        return $missing;
    }
}
