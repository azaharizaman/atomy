<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Audit;

/**
 * DTO representing a segregation of duties compliance report.
 */
final readonly class SegregationOfDutiesReportData
{
    /**
     * @param string $reportId Report identifier
     * @param string $tenantId Tenant context
     * @param \DateTimeImmutable $periodStart Period start date
     * @param \DateTimeImmutable $periodEnd Period end date
     * @param \DateTimeImmutable $generatedAt When report was generated
     * @param string $generatedBy User who generated report
     * @param array $conflictMatrix Role conflict matrix
     * @param array $violationSummary Summary of SoD violations
     * @param array $userRoleAssignments User to role mappings
     * @param array $incompatibleDutyPairs Incompatible duty pairs definition
     * @param array $riskAssessment Risk assessment by area
     * @param array $mitigatingControls Compensating controls in place
     * @param bool $isCompliant Overall compliance status
     * @param string|null $conclusion Report conclusion
     * @param array $metadata Additional report metadata
     */
    public function __construct(
        public string $reportId,
        public string $tenantId,
        public \DateTimeImmutable $periodStart,
        public \DateTimeImmutable $periodEnd,
        public \DateTimeImmutable $generatedAt,
        public string $generatedBy,
        public array $conflictMatrix = [],
        public array $violationSummary = [],
        public array $userRoleAssignments = [],
        public array $incompatibleDutyPairs = [],
        public array $riskAssessment = [],
        public array $mitigatingControls = [],
        public bool $isCompliant = false,
        public ?string $conclusion = null,
        public array $metadata = [],
    ) {}

    /**
     * Get total violation count.
     */
    public function getViolationCount(): int
    {
        return count($this->violationSummary);
    }

    /**
     * Get violations by severity.
     *
     * @return array<string, int>
     */
    public function getViolationsBySeverity(): array
    {
        $bySeverity = [
            'HIGH' => 0,
            'MEDIUM' => 0,
            'LOW' => 0,
        ];

        foreach ($this->violationSummary as $violation) {
            $severity = $violation['severity'] ?? 'MEDIUM';
            if (isset($bySeverity[$severity])) {
                $bySeverity[$severity]++;
            }
        }

        return $bySeverity;
    }

    /**
     * Get high severity violation count.
     */
    public function getHighSeverityViolationCount(): int
    {
        return $this->getViolationsBySeverity()['HIGH'];
    }

    /**
     * Get count of users with SoD conflicts.
     */
    public function getAffectedUserCount(): int
    {
        $affectedUsers = [];
        foreach ($this->violationSummary as $violation) {
            if (isset($violation['user_id'])) {
                $affectedUsers[$violation['user_id']] = true;
            }
        }
        return count($affectedUsers);
    }

    /**
     * Get violations for specific user.
     */
    public function getViolationsForUser(string $userId): array
    {
        return array_filter(
            $this->violationSummary,
            fn(array $violation) => ($violation['user_id'] ?? '') === $userId,
        );
    }

    /**
     * Check if specific role combination is allowed.
     */
    public function isRoleCombinationAllowed(string $role1, string $role2): bool
    {
        foreach ($this->incompatibleDutyPairs as $pair) {
            $duties = $pair['duties'] ?? [];
            if (in_array($role1, $duties, true) && in_array($role2, $duties, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all incompatible role pairs.
     */
    public function getIncompatibleRoles(): array
    {
        $pairs = [];
        foreach ($this->incompatibleDutyPairs as $pair) {
            $pairs[] = [
                'role1' => $pair['duties'][0] ?? '',
                'role2' => $pair['duties'][1] ?? '',
                'risk' => $pair['risk'] ?? 'Unknown risk',
            ];
        }
        return $pairs;
    }

    /**
     * Get mitigating control for specific violation.
     */
    public function getMitigatingControl(string $violationType): ?array
    {
        foreach ($this->mitigatingControls as $control) {
            if (($control['violation_type'] ?? '') === $violationType) {
                return $control;
            }
        }
        return null;
    }

    /**
     * Check if all violations have mitigating controls.
     */
    public function allViolationsHaveMitigatingControls(): bool
    {
        $violationTypes = array_unique(array_column($this->violationSummary, 'violation_type'));
        $mitigatedTypes = array_column($this->mitigatingControls, 'violation_type');

        foreach ($violationTypes as $type) {
            if (!in_array($type, $mitigatedTypes, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get risk level for specific area.
     */
    public function getRiskLevel(string $area): string
    {
        foreach ($this->riskAssessment as $assessment) {
            if (($assessment['area'] ?? '') === $area) {
                return $assessment['risk_level'] ?? 'UNKNOWN';
            }
        }
        return 'NOT_ASSESSED';
    }

    /**
     * Get overall risk level.
     */
    public function getOverallRiskLevel(): string
    {
        if ($this->getHighSeverityViolationCount() > 0) {
            return 'HIGH';
        }

        if ($this->getViolationCount() > 5) {
            return 'MEDIUM';
        }

        if ($this->getViolationCount() > 0) {
            return 'LOW';
        }

        return 'MINIMAL';
    }

    /**
     * Get period covered description.
     */
    public function getPeriodCovered(): string
    {
        return sprintf(
            '%s to %s',
            $this->periodStart->format('Y-m-d'),
            $this->periodEnd->format('Y-m-d'),
        );
    }

    /**
     * Get executive summary.
     */
    public function getExecutiveSummary(): array
    {
        $violationsBySeverity = $this->getViolationsBySeverity();

        return [
            'report_id' => $this->reportId,
            'period_covered' => $this->getPeriodCovered(),
            'generated_at' => $this->generatedAt->format('Y-m-d H:i:s'),
            'is_compliant' => $this->isCompliant,
            'overall_risk_level' => $this->getOverallRiskLevel(),
            'violations' => [
                'total' => $this->getViolationCount(),
                'high_severity' => $violationsBySeverity['HIGH'],
                'medium_severity' => $violationsBySeverity['MEDIUM'],
                'low_severity' => $violationsBySeverity['LOW'],
            ],
            'affected_users' => $this->getAffectedUserCount(),
            'incompatible_duty_pairs' => count($this->incompatibleDutyPairs),
            'mitigating_controls' => count($this->mitigatingControls),
            'all_mitigated' => $this->allViolationsHaveMitigatingControls(),
            'conclusion' => $this->conclusion,
        ];
    }

    /**
     * Generate recommendations based on violations.
     */
    public function getRecommendations(): array
    {
        $recommendations = [];

        if ($this->getHighSeverityViolationCount() > 0) {
            $recommendations[] = [
                'priority' => 'CRITICAL',
                'action' => 'Immediately review and remediate high-severity SoD violations',
            ];
        }

        if (!$this->allViolationsHaveMitigatingControls()) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'action' => 'Implement mitigating controls for unaddressed violations',
            ];
        }

        if ($this->getAffectedUserCount() > 5) {
            $recommendations[] = [
                'priority' => 'MEDIUM',
                'action' => 'Review role assignment process to prevent future conflicts',
            ];
        }

        return $recommendations;
    }
}
