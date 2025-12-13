<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Rules;

use Nexus\ProcurementOperations\DTOs\Audit\AuditFindingData;
use Nexus\ProcurementOperations\DTOs\Audit\ControlTestResultData;
use Nexus\ProcurementOperations\DTOs\Audit\SegregationOfDutiesReportData;
use Nexus\ProcurementOperations\Enums\AuditFindingSeverity;
use Nexus\ProcurementOperations\Enums\ControlArea;

/**
 * Rule for validating audit compliance requirements.
 */
final readonly class AuditComplianceRule
{
    /**
     * Check if control test results meet SOX 404 requirements.
     *
     * @param array<ControlTestResultData> $testResults Control test results
     * @param float $tolerableExceptionRate Maximum tolerable exception rate
     * @return AuditComplianceRuleResult Validation result
     */
    public function checkControlTestCompliance(
        array $testResults,
        float $tolerableExceptionRate = 5.0,
    ): AuditComplianceRuleResult {
        if (empty($testResults)) {
            return AuditComplianceRuleResult::fail(
                message: 'No control test results available for compliance check',
                reason: 'NO_TEST_RESULTS',
            );
        }

        $failedTests = [];
        $insufficientSamples = [];

        foreach ($testResults as $result) {
            if (!$result->isPassing) {
                $failedTests[] = $result->controlArea->value;
            }

            if (!$result->meetsMinimumSampleSize()) {
                $insufficientSamples[] = [
                    'control' => $result->controlArea->value,
                    'required' => $result->controlArea->getTestSampleSize(),
                    'actual' => $result->sampleSize,
                ];
            }

            if ($result->getExceptionRate() > $tolerableExceptionRate) {
                $failedTests[] = $result->controlArea->value . ' (exception rate: ' . $result->getExceptionRate() . '%)';
            }
        }

        if (!empty($failedTests)) {
            return AuditComplianceRuleResult::fail(
                message: 'Control tests failed compliance check',
                reason: 'CONTROL_FAILURES',
                failedControls: $failedTests,
                insufficientSamples: $insufficientSamples,
            );
        }

        if (!empty($insufficientSamples)) {
            return AuditComplianceRuleResult::warn(
                message: 'Some controls have insufficient sample sizes',
                insufficientSamples: $insufficientSamples,
            );
        }

        return AuditComplianceRuleResult::pass(
            message: 'All control tests meet SOX 404 compliance requirements',
            testedControls: count($testResults),
        );
    }

    /**
     * Check if audit findings allow clean opinion.
     *
     * @param array<AuditFindingData> $findings Audit findings
     * @return AuditComplianceRuleResult Validation result
     */
    public function checkAuditFindingsCompliance(array $findings): AuditComplianceRuleResult
    {
        $materialWeaknesses = [];
        $significantDeficiencies = [];
        $openFindings = [];

        foreach ($findings as $finding) {
            if (!$finding->isOpen()) {
                continue;
            }

            $openFindings[] = $finding->findingId;

            if ($finding->severity === AuditFindingSeverity::MATERIAL_WEAKNESS) {
                $materialWeaknesses[] = $finding->findingId;
            } elseif ($finding->severity === AuditFindingSeverity::SIGNIFICANT_DEFICIENCY) {
                $significantDeficiencies[] = $finding->findingId;
            }
        }

        if (!empty($materialWeaknesses)) {
            return AuditComplianceRuleResult::fail(
                message: 'Material weaknesses prevent clean audit opinion',
                reason: 'MATERIAL_WEAKNESSES',
                materialWeaknesses: $materialWeaknesses,
                significantDeficiencies: $significantDeficiencies,
                openFindings: count($openFindings),
            );
        }

        if (!empty($significantDeficiencies)) {
            return AuditComplianceRuleResult::warn(
                message: 'Significant deficiencies require disclosure',
                significantDeficiencies: $significantDeficiencies,
                openFindings: count($openFindings),
            );
        }

        return AuditComplianceRuleResult::pass(
            message: 'No material weaknesses or significant deficiencies found',
            openFindings: count($openFindings),
        );
    }

    /**
     * Check segregation of duties compliance.
     *
     * @param SegregationOfDutiesReportData $sodReport SoD report
     * @return AuditComplianceRuleResult Validation result
     */
    public function checkSegregationOfDutiesCompliance(
        SegregationOfDutiesReportData $sodReport,
    ): AuditComplianceRuleResult {
        if ($sodReport->isCompliant) {
            return AuditComplianceRuleResult::pass(
                message: 'Segregation of duties requirements met',
            );
        }

        $violationsBySeverity = $sodReport->getViolationsBySeverity();
        $highSeverity = $violationsBySeverity['HIGH'];

        if ($highSeverity > 0) {
            return AuditComplianceRuleResult::fail(
                message: 'High-severity segregation of duties violations detected',
                reason: 'SOD_VIOLATIONS',
                sodViolations: $sodReport->getViolationCount(),
                highSeverityViolations: $highSeverity,
                affectedUsers: $sodReport->getAffectedUserCount(),
            );
        }

        if ($sodReport->getViolationCount() > 0) {
            if ($sodReport->allViolationsHaveMitigatingControls()) {
                return AuditComplianceRuleResult::warn(
                    message: 'SoD violations exist but have mitigating controls',
                    sodViolations: $sodReport->getViolationCount(),
                    hasMitigatingControls: true,
                );
            }

            return AuditComplianceRuleResult::warn(
                message: 'SoD violations require mitigating controls',
                sodViolations: $sodReport->getViolationCount(),
                hasMitigatingControls: false,
            );
        }

        return AuditComplianceRuleResult::pass(
            message: 'No segregation of duties violations',
        );
    }

    /**
     * Check overall audit readiness.
     *
     * @param array<ControlTestResultData> $controlTests Control test results
     * @param array<AuditFindingData> $findings Audit findings
     * @param SegregationOfDutiesReportData $sodReport SoD report
     * @return AuditComplianceRuleResult Comprehensive compliance result
     */
    public function checkOverallAuditReadiness(
        array $controlTests,
        array $findings,
        SegregationOfDutiesReportData $sodReport,
    ): AuditComplianceRuleResult {
        $controlResult = $this->checkControlTestCompliance($controlTests);
        $findingsResult = $this->checkAuditFindingsCompliance($findings);
        $sodResult = $this->checkSegregationOfDutiesCompliance($sodReport);

        $allPassed = $controlResult->passed && $findingsResult->passed && $sodResult->passed;
        $hasFailures = !$controlResult->passed || !$findingsResult->passed || !$sodResult->passed;

        if (!$allPassed) {
            $failureReasons = [];
            if (!$controlResult->passed) {
                $failureReasons[] = 'Control test failures';
            }
            if (!$findingsResult->passed && $findingsResult->reason === 'MATERIAL_WEAKNESSES') {
                $failureReasons[] = 'Material weaknesses';
            }
            if (!$sodResult->passed && $sodResult->reason === 'SOD_VIOLATIONS') {
                $failureReasons[] = 'SoD violations';
            }

            if (!empty($failureReasons)) {
                return AuditComplianceRuleResult::fail(
                    message: 'Audit readiness check failed: ' . implode(', ', $failureReasons),
                    reason: 'MULTIPLE_FAILURES',
                    subResults: [
                        'control_tests' => $controlResult->toArray(),
                        'findings' => $findingsResult->toArray(),
                        'sod' => $sodResult->toArray(),
                    ],
                );
            }
        }

        if ($hasFailures) {
            return AuditComplianceRuleResult::warn(
                message: 'Audit readiness check passed with warnings',
                subResults: [
                    'control_tests' => $controlResult->toArray(),
                    'findings' => $findingsResult->toArray(),
                    'sod' => $sodResult->toArray(),
                ],
            );
        }

        return AuditComplianceRuleResult::pass(
            message: 'Organization is ready for audit',
            subResults: [
                'control_tests' => $controlResult->toArray(),
                'findings' => $findingsResult->toArray(),
                'sod' => $sodResult->toArray(),
            ],
        );
    }
}

/**
 * Result object for audit compliance rule validation.
 */
final readonly class AuditComplianceRuleResult
{
    private function __construct(
        public bool $passed,
        public bool $isWarning,
        public string $message,
        public ?string $reason = null,
        public array $failedControls = [],
        public array $insufficientSamples = [],
        public array $materialWeaknesses = [],
        public array $significantDeficiencies = [],
        public ?int $openFindings = null,
        public ?int $testedControls = null,
        public ?int $sodViolations = null,
        public ?int $highSeverityViolations = null,
        public ?int $affectedUsers = null,
        public ?bool $hasMitigatingControls = null,
        public array $subResults = [],
    ) {}

    public static function pass(
        string $message,
        ?int $testedControls = null,
        ?int $openFindings = null,
        array $subResults = [],
    ): self {
        return new self(
            passed: true,
            isWarning: false,
            message: $message,
            testedControls: $testedControls,
            openFindings: $openFindings,
            subResults: $subResults,
        );
    }

    public static function warn(
        string $message,
        array $insufficientSamples = [],
        array $significantDeficiencies = [],
        ?int $openFindings = null,
        ?int $sodViolations = null,
        ?bool $hasMitigatingControls = null,
        array $subResults = [],
    ): self {
        return new self(
            passed: true,
            isWarning: true,
            message: $message,
            insufficientSamples: $insufficientSamples,
            significantDeficiencies: $significantDeficiencies,
            openFindings: $openFindings,
            sodViolations: $sodViolations,
            hasMitigatingControls: $hasMitigatingControls,
            subResults: $subResults,
        );
    }

    public static function fail(
        string $message,
        string $reason,
        array $failedControls = [],
        array $insufficientSamples = [],
        array $materialWeaknesses = [],
        array $significantDeficiencies = [],
        ?int $openFindings = null,
        ?int $sodViolations = null,
        ?int $highSeverityViolations = null,
        ?int $affectedUsers = null,
        array $subResults = [],
    ): self {
        return new self(
            passed: false,
            isWarning: false,
            message: $message,
            reason: $reason,
            failedControls: $failedControls,
            insufficientSamples: $insufficientSamples,
            materialWeaknesses: $materialWeaknesses,
            significantDeficiencies: $significantDeficiencies,
            openFindings: $openFindings,
            sodViolations: $sodViolations,
            highSeverityViolations: $highSeverityViolations,
            affectedUsers: $affectedUsers,
            subResults: $subResults,
        );
    }

    public function toArray(): array
    {
        return [
            'passed' => $this->passed,
            'is_warning' => $this->isWarning,
            'message' => $this->message,
            'reason' => $this->reason,
            'failed_controls' => $this->failedControls,
            'insufficient_samples' => $this->insufficientSamples,
            'material_weaknesses' => $this->materialWeaknesses,
            'significant_deficiencies' => $this->significantDeficiencies,
            'open_findings' => $this->openFindings,
            'tested_controls' => $this->testedControls,
            'sod_violations' => $this->sodViolations,
            'high_severity_violations' => $this->highSeverityViolations,
            'affected_users' => $this->affectedUsers,
            'has_mitigating_controls' => $this->hasMitigatingControls,
        ];
    }
}
