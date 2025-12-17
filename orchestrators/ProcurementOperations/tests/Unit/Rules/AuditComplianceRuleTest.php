<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Rules;

use Nexus\ProcurementOperations\DTOs\Audit\AuditFindingData;
use Nexus\ProcurementOperations\DTOs\Audit\ControlTestResultData;
use Nexus\ProcurementOperations\DTOs\Audit\SegregationOfDutiesReportData;
use Nexus\ProcurementOperations\Enums\AuditFindingSeverity;
use Nexus\ProcurementOperations\Enums\ControlArea;
use Nexus\ProcurementOperations\Rules\AuditComplianceRule;
use Nexus\ProcurementOperations\Rules\AuditComplianceRuleResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuditComplianceRule::class)]
#[CoversClass(AuditComplianceRuleResult::class)]
final class AuditComplianceRuleTest extends TestCase
{
    private AuditComplianceRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new AuditComplianceRule();
    }

    #[Test]
    public function checkControlTestCompliance_noResults_returnsFail(): void
    {
        $result = $this->rule->checkControlTestCompliance([]);

        $this->assertFalse($result->passed);
        $this->assertEquals('NO_TEST_RESULTS', $result->reason);
    }

    #[Test]
    public function checkControlTestCompliance_allPassing_returnsPass(): void
    {
        $testResults = [
            new ControlTestResultData(
                controlArea: ControlArea::PURCHASE_REQUISITION,
                testDate: new \DateTimeImmutable(),
                testerId: 'tester-1',
                sampleSize: 50,
                exceptionsFound: 0,
                isPassing: true,
                testProcedure: 'Verified all requisitions have proper approval',
                conclusions: 'Control operating effectively',
            ),
            new ControlTestResultData(
                controlArea: ControlArea::PURCHASE_ORDER_APPROVAL,
                testDate: new \DateTimeImmutable(),
                testerId: 'tester-1',
                sampleSize: 45,
                exceptionsFound: 1,
                isPassing: true,
                testProcedure: 'Verified PO approval workflow',
                conclusions: 'Control operating effectively with minor exceptions',
            ),
        ];

        $result = $this->rule->checkControlTestCompliance($testResults);

        $this->assertTrue($result->passed);
        $this->assertEquals(2, $result->testedControls);
    }

    #[Test]
    public function checkControlTestCompliance_failedTests_returnsFail(): void
    {
        $testResults = [
            new ControlTestResultData(
                controlArea: ControlArea::THREE_WAY_MATCH,
                testDate: new \DateTimeImmutable(),
                testerId: 'tester-1',
                sampleSize: 50,
                exceptionsFound: 10, // 20% exception rate
                isPassing: false,
                testProcedure: 'Verified 3-way match process',
                conclusions: 'Control not operating effectively',
            ),
        ];

        $result = $this->rule->checkControlTestCompliance($testResults);

        $this->assertFalse($result->passed);
        $this->assertEquals('CONTROL_FAILURES', $result->reason);
        $this->assertNotEmpty($result->failedControls);
    }

    #[Test]
    public function checkControlTestCompliance_exceptionRateExceeded_returnsFail(): void
    {
        $testResults = [
            new ControlTestResultData(
                controlArea: ControlArea::VENDOR_MASTER_DATA,
                testDate: new \DateTimeImmutable(),
                testerId: 'tester-1',
                sampleSize: 100,
                exceptionsFound: 8, // 8% exception rate (> 5% tolerance)
                isPassing: true, // Marked passing but exception rate is too high
                testProcedure: 'Verified vendor data accuracy',
                conclusions: 'Some exceptions noted',
            ),
        ];

        $result = $this->rule->checkControlTestCompliance($testResults, tolerableExceptionRate: 5.0);

        $this->assertFalse($result->passed);
        $this->assertEquals('CONTROL_FAILURES', $result->reason);
    }

    #[Test]
    public function checkAuditFindingsCompliance_materialWeaknesses_returnsFail(): void
    {
        $findings = [
            new AuditFindingData(
                findingId: 'FND-001',
                controlArea: ControlArea::SEGREGATION_OF_DUTIES,
                severity: AuditFindingSeverity::MATERIAL_WEAKNESS,
                description: 'Same user can create and approve POs',
                rootCause: 'System configuration error',
                recommendation: 'Implement proper role segregation',
                identifiedDate: new \DateTimeImmutable('-30 days'),
                remediationDeadline: new \DateTimeImmutable('+60 days'),
            ),
        ];

        $result = $this->rule->checkAuditFindingsCompliance($findings);

        $this->assertFalse($result->passed);
        $this->assertEquals('MATERIAL_WEAKNESSES', $result->reason);
        $this->assertNotEmpty($result->materialWeaknesses);
    }

    #[Test]
    public function checkAuditFindingsCompliance_significantDeficiencies_returnsWarning(): void
    {
        $findings = [
            new AuditFindingData(
                findingId: 'FND-002',
                controlArea: ControlArea::PAYMENT_AUTHORIZATION,
                severity: AuditFindingSeverity::SIGNIFICANT_DEFICIENCY,
                description: 'Payment approval limits not enforced consistently',
                rootCause: 'Training gap',
                recommendation: 'Provide refresher training',
                identifiedDate: new \DateTimeImmutable('-15 days'),
                remediationDeadline: new \DateTimeImmutable('+45 days'),
            ),
        ];

        $result = $this->rule->checkAuditFindingsCompliance($findings);

        $this->assertTrue($result->passed);
        $this->assertTrue($result->isWarning);
        $this->assertNotEmpty($result->significantDeficiencies);
    }

    #[Test]
    public function checkAuditFindingsCompliance_closedFindings_ignored(): void
    {
        $finding = new AuditFindingData(
            findingId: 'FND-003',
            controlArea: ControlArea::VENDOR_MASTER_DATA,
            severity: AuditFindingSeverity::MATERIAL_WEAKNESS,
            description: 'Historical issue',
            rootCause: 'Legacy system',
            recommendation: 'Already fixed',
            identifiedDate: new \DateTimeImmutable('-90 days'),
            remediationDeadline: new \DateTimeImmutable('-30 days'),
        );

        // Close the finding
        $closedFinding = $finding->withResolution(
            new \DateTimeImmutable('-15 days'),
            'remediation_validated',
            'Issue has been fully remediated',
        );

        $result = $this->rule->checkAuditFindingsCompliance([$closedFinding]);

        $this->assertTrue($result->passed);
        $this->assertFalse($result->isWarning);
    }

    #[Test]
    public function checkSegregationOfDutiesCompliance_compliant_returnsPass(): void
    {
        $sodReport = new SegregationOfDutiesReportData(
            reportId: 'SOD-001',
            tenantId: 'tenant-1',
            generatedAt: new \DateTimeImmutable(),
            periodStart: new \DateTimeImmutable('-30 days'),
            periodEnd: new \DateTimeImmutable(),
            conflictMatrix: [],
            violationSummary: [],
            mitigatingControls: [],
            isCompliant: true,
        );

        $result = $this->rule->checkSegregationOfDutiesCompliance($sodReport);

        $this->assertTrue($result->passed);
        $this->assertFalse($result->isWarning);
    }

    #[Test]
    public function checkSegregationOfDutiesCompliance_highSeverityViolations_returnsFail(): void
    {
        $sodReport = new SegregationOfDutiesReportData(
            reportId: 'SOD-002',
            tenantId: 'tenant-1',
            generatedAt: new \DateTimeImmutable(),
            periodStart: new \DateTimeImmutable('-30 days'),
            periodEnd: new \DateTimeImmutable(),
            conflictMatrix: [
                [
                    'user_id' => 'user-1',
                    'conflicting_roles' => ['PO Creator', 'PO Approver'],
                    'severity' => 'HIGH',
                ],
            ],
            violationSummary: [
                [
                    'violation_type' => 'PO_CREATE_APPROVE',
                    'count' => 1,
                    'severity' => 'HIGH',
                    'affected_users' => ['user-1'],
                ],
            ],
            mitigatingControls: [],
            isCompliant: false,
        );

        $result = $this->rule->checkSegregationOfDutiesCompliance($sodReport);

        $this->assertFalse($result->passed);
        $this->assertEquals('SOD_VIOLATIONS', $result->reason);
        $this->assertGreaterThan(0, $result->highSeverityViolations);
    }

    #[Test]
    public function checkSegregationOfDutiesCompliance_withMitigatingControls_returnsWarning(): void
    {
        $sodReport = new SegregationOfDutiesReportData(
            reportId: 'SOD-003',
            tenantId: 'tenant-1',
            generatedAt: new \DateTimeImmutable(),
            periodStart: new \DateTimeImmutable('-30 days'),
            periodEnd: new \DateTimeImmutable(),
            conflictMatrix: [
                [
                    'user_id' => 'user-1',
                    'conflicting_roles' => ['Invoice Entry', 'Payment Entry'],
                    'severity' => 'MEDIUM',
                ],
            ],
            violationSummary: [
                [
                    'violation_type' => 'INVOICE_PAYMENT',
                    'count' => 1,
                    'severity' => 'MEDIUM',
                    'affected_users' => ['user-1'],
                ],
            ],
            mitigatingControls: [
                [
                    'violation_type' => 'INVOICE_PAYMENT',
                    'control' => 'Manager review of all payments',
                    'control_owner' => 'manager-1',
                ],
            ],
            isCompliant: false,
        );

        $result = $this->rule->checkSegregationOfDutiesCompliance($sodReport);

        $this->assertTrue($result->passed);
        $this->assertTrue($result->isWarning);
        $this->assertTrue($result->hasMitigatingControls);
    }

    #[Test]
    public function checkOverallAuditReadiness_allPass_returnsPass(): void
    {
        $controlTests = [
            new ControlTestResultData(
                controlArea: ControlArea::PURCHASE_REQUISITION,
                testDate: new \DateTimeImmutable(),
                testerId: 'tester-1',
                sampleSize: 50,
                exceptionsFound: 0,
                isPassing: true,
                testProcedure: 'Test procedure',
                conclusions: 'Effective',
            ),
        ];

        $findings = [];

        $sodReport = new SegregationOfDutiesReportData(
            reportId: 'SOD-004',
            tenantId: 'tenant-1',
            generatedAt: new \DateTimeImmutable(),
            periodStart: new \DateTimeImmutable('-30 days'),
            periodEnd: new \DateTimeImmutable(),
            conflictMatrix: [],
            violationSummary: [],
            mitigatingControls: [],
            isCompliant: true,
        );

        $result = $this->rule->checkOverallAuditReadiness($controlTests, $findings, $sodReport);

        $this->assertTrue($result->passed);
        $this->assertStringContainsString('ready for audit', strtolower($result->message));
    }

    #[Test]
    public function checkOverallAuditReadiness_multipleFailures_returnsFail(): void
    {
        $controlTests = [
            new ControlTestResultData(
                controlArea: ControlArea::THREE_WAY_MATCH,
                testDate: new \DateTimeImmutable(),
                testerId: 'tester-1',
                sampleSize: 50,
                exceptionsFound: 10,
                isPassing: false,
                testProcedure: 'Test procedure',
                conclusions: 'Not effective',
            ),
        ];

        $findings = [
            new AuditFindingData(
                findingId: 'FND-001',
                controlArea: ControlArea::SEGREGATION_OF_DUTIES,
                severity: AuditFindingSeverity::MATERIAL_WEAKNESS,
                description: 'Critical issue',
                rootCause: 'System issue',
                recommendation: 'Fix immediately',
                identifiedDate: new \DateTimeImmutable(),
                remediationDeadline: new \DateTimeImmutable('+30 days'),
            ),
        ];

        $sodReport = new SegregationOfDutiesReportData(
            reportId: 'SOD-005',
            tenantId: 'tenant-1',
            generatedAt: new \DateTimeImmutable(),
            periodStart: new \DateTimeImmutable('-30 days'),
            periodEnd: new \DateTimeImmutable(),
            conflictMatrix: [],
            violationSummary: [],
            mitigatingControls: [],
            isCompliant: true,
        );

        $result = $this->rule->checkOverallAuditReadiness($controlTests, $findings, $sodReport);

        $this->assertFalse($result->passed);
        $this->assertEquals('MULTIPLE_FAILURES', $result->reason);
        $this->assertNotEmpty($result->subResults);
    }

    #[Test]
    public function resultToArray_containsExpectedStructure(): void
    {
        $result = AuditComplianceRuleResult::fail(
            message: 'Test failure',
            reason: 'TEST_REASON',
            failedControls: ['CONTROL_1'],
            openFindings: 5,
        );

        $array = $result->toArray();

        $this->assertArrayHasKey('passed', $array);
        $this->assertArrayHasKey('is_warning', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('reason', $array);
        $this->assertFalse($array['passed']);
    }
}
