<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Rules;

use Nexus\ProcurementOperations\Enums\ControlArea;
use Nexus\ProcurementOperations\Rules\ControlTestingRule;
use Nexus\ProcurementOperations\Rules\ControlTestingRuleResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ControlTestingRule::class)]
#[CoversClass(ControlTestingRuleResult::class)]
final class ControlTestingRuleTest extends TestCase
{
    private ControlTestingRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new ControlTestingRule();
    }

    #[Test]
    public function validateSampleSize_sufficientSample_returnsPass(): void
    {
        $result = $this->rule->validateSampleSize(
            controlArea: ControlArea::PURCHASE_REQUISITION,
            proposedSampleSize: 50,
            populationSize: 1000,
        );

        $this->assertTrue($result->passed);
        $this->assertFalse($result->isWarning);
    }

    #[Test]
    public function validateSampleSize_insufficientSample_returnsFail(): void
    {
        $result = $this->rule->validateSampleSize(
            controlArea: ControlArea::THREE_WAY_MATCH, // Key control requires 45
            proposedSampleSize: 20,
            populationSize: 500,
        );

        $this->assertFalse($result->passed);
        $this->assertEquals('INSUFFICIENT_SAMPLE_SIZE', $result->reason);
        $this->assertNotNull($result->requiredSampleSize);
    }

    #[Test]
    public function validateSampleSize_keyControl_requiresLargerSample(): void
    {
        // Key control should require minimum 45 samples
        $result = $this->rule->validateSampleSize(
            controlArea: ControlArea::THREE_WAY_MATCH,
            proposedSampleSize: 30, // Below 45 minimum for key controls
            populationSize: 500,
        );

        $this->assertFalse($result->passed);
        $this->assertEquals('INSUFFICIENT_SAMPLE_SIZE', $result->reason);
    }

    #[Test]
    public function validateSampleSize_smallPopulation_adjustsMinimum(): void
    {
        // For populations < 250, minimum is 10% of population
        $result = $this->rule->validateSampleSize(
            controlArea: ControlArea::VENDOR_MASTER_DATA,
            proposedSampleSize: 20, // 10% of 200
            populationSize: 200,
        );

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function validateSampleSize_sampleExceedsPopulation_adjusts(): void
    {
        $result = $this->rule->validateSampleSize(
            controlArea: ControlArea::PURCHASE_REQUISITION,
            proposedSampleSize: 15,
            populationSize: 15, // Population equals sample
        );

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function validateExceptionHandling_noExceptions_returnsPass(): void
    {
        $result = $this->rule->validateExceptionHandling(
            exceptionsFound: 0,
            sampleSize: 50,
            controlArea: ControlArea::PURCHASE_ORDER_APPROVAL,
        );

        $this->assertTrue($result->passed);
        $this->assertFalse($result->isWarning);
        $this->assertEquals(0, $result->exceptionsFound);
        $this->assertEquals(0.0, $result->exceptionRate);
    }

    #[Test]
    public function validateExceptionHandling_singleExceptionKeyControl_returnsWarning(): void
    {
        $result = $this->rule->validateExceptionHandling(
            exceptionsFound: 1,
            sampleSize: 50,
            controlArea: ControlArea::THREE_WAY_MATCH,
            isKeyControl: true,
        );

        $this->assertTrue($result->passed);
        $this->assertTrue($result->isWarning);
        $this->assertEquals('INVESTIGATE_ROOT_CAUSE', $result->recommendedAction);
    }

    #[Test]
    public function validateExceptionHandling_exceededTolerance_returnsFail(): void
    {
        $result = $this->rule->validateExceptionHandling(
            exceptionsFound: 10, // 20% rate
            sampleSize: 50,
            controlArea: ControlArea::PAYMENT_AUTHORIZATION,
        );

        $this->assertFalse($result->passed);
        $this->assertEquals('EXCEPTION_RATE_EXCEEDED', $result->reason);
        $this->assertEquals('EXPAND_TESTING', $result->recommendedAction);
        $this->assertEquals(100, $result->expandedSampleSize); // 2x original
    }

    #[Test]
    public function validateExceptionHandling_withinTolerance_returnsWarning(): void
    {
        $result = $this->rule->validateExceptionHandling(
            exceptionsFound: 2, // 4% rate (within 5%)
            sampleSize: 50,
            controlArea: ControlArea::VENDOR_MASTER_DATA,
        );

        $this->assertTrue($result->passed);
        $this->assertTrue($result->isWarning);
        $this->assertEquals('DOCUMENT_AND_MONITOR', $result->recommendedAction);
    }

    #[Test]
    public function validateExceptionHandling_zeroSampleSize_returnsFail(): void
    {
        $result = $this->rule->validateExceptionHandling(
            exceptionsFound: 0,
            sampleSize: 0,
            controlArea: ControlArea::PURCHASE_REQUISITION,
        );

        $this->assertFalse($result->passed);
        $this->assertEquals('INVALID_SAMPLE_SIZE', $result->reason);
    }

    #[Test]
    public function validateTestTiming_withinAcceptableRange_returnsPass(): void
    {
        $lastTestDate = new \DateTimeImmutable('-60 days');
        $periodEndDate = new \DateTimeImmutable();

        $result = $this->rule->validateTestTiming(
            controlArea: ControlArea::THREE_WAY_MATCH,
            lastTestDate: $lastTestDate,
            periodEndDate: $periodEndDate,
        );

        $this->assertTrue($result->passed);
        $this->assertFalse($result->isWarning);
    }

    #[Test]
    public function validateTestTiming_tooEarly_returnsFail(): void
    {
        $lastTestDate = new \DateTimeImmutable('-150 days');
        $periodEndDate = new \DateTimeImmutable();

        $result = $this->rule->validateTestTiming(
            controlArea: ControlArea::THREE_WAY_MATCH, // Key control: 90 day max
            lastTestDate: $lastTestDate,
            periodEndDate: $periodEndDate,
        );

        $this->assertFalse($result->passed);
        $this->assertEquals('TEST_TOO_EARLY', $result->reason);
        $this->assertEquals('ROLLFORWARD_REQUIRED', $result->recommendedAction);
    }

    #[Test]
    public function validateTestTiming_afterPeriodEnd_returnsWarningOrFail(): void
    {
        $periodEndDate = new \DateTimeImmutable('-30 days');
        $lastTestDate = new \DateTimeImmutable(); // 30 days after period end

        $result = $this->rule->validateTestTiming(
            controlArea: ControlArea::VENDOR_MASTER_DATA,
            lastTestDate: $lastTestDate,
            periodEndDate: $periodEndDate,
        );

        $this->assertTrue($result->passed);
        $this->assertTrue($result->isWarning);
        $this->assertEquals('PERFORM_ROLLBACK', $result->recommendedAction);
    }

    #[Test]
    public function validateTestTiming_tooLateAfterPeriodEnd_returnsFail(): void
    {
        $periodEndDate = new \DateTimeImmutable('-90 days');
        $lastTestDate = new \DateTimeImmutable(); // 90 days after period end

        $result = $this->rule->validateTestTiming(
            controlArea: ControlArea::PAYMENT_AUTHORIZATION,
            lastTestDate: $lastTestDate,
            periodEndDate: $periodEndDate,
        );

        $this->assertFalse($result->passed);
        $this->assertEquals('TEST_TOO_LATE', $result->reason);
    }

    #[Test]
    public function validateTesterIndependence_independent_returnsPass(): void
    {
        $result = $this->rule->validateTesterIndependence(
            testerId: 'internal-auditor-1',
            controlOwnerId: 'manager-1',
            controlOperatorIds: ['clerk-1', 'clerk-2'],
            controlArea: ControlArea::PURCHASE_ORDER_APPROVAL,
        );

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function validateTesterIndependence_testerIsOwner_returnsFail(): void
    {
        $result = $this->rule->validateTesterIndependence(
            testerId: 'manager-1',
            controlOwnerId: 'manager-1',
            controlOperatorIds: ['clerk-1'],
            controlArea: ControlArea::PURCHASE_ORDER_APPROVAL,
        );

        $this->assertFalse($result->passed);
        $this->assertEquals('TESTER_IS_OWNER', $result->reason);
        $this->assertEquals('manager-1', $result->conflictingUserId);
    }

    #[Test]
    public function validateTesterIndependence_testerIsOperator_returnsFail(): void
    {
        $result = $this->rule->validateTesterIndependence(
            testerId: 'clerk-2',
            controlOwnerId: 'manager-1',
            controlOperatorIds: ['clerk-1', 'clerk-2'],
            controlArea: ControlArea::VENDOR_MASTER_DATA,
        );

        $this->assertFalse($result->passed);
        $this->assertEquals('TESTER_IS_OPERATOR', $result->reason);
    }

    #[Test]
    public function validateTestCoverage_allTested_returnsPass(): void
    {
        $testedControls = ControlArea::cases(); // All controls tested

        $result = $this->rule->validateTestCoverage($testedControls);

        $this->assertTrue($result->passed);
        $this->assertFalse($result->isWarning);
        $this->assertEquals(count(ControlArea::cases()), $result->testedControlCount);
    }

    #[Test]
    public function validateTestCoverage_missingKeyControls_returnsFail(): void
    {
        // Test only some controls, excluding key controls
        $testedControls = [
            ControlArea::VENDOR_MASTER_DATA,
            ControlArea::VENDOR_CORRESPONDENCE,
        ];

        $result = $this->rule->validateTestCoverage($testedControls);

        $this->assertFalse($result->passed);
        $this->assertEquals('MISSING_KEY_CONTROLS', $result->reason);
        $this->assertNotEmpty($result->missingKeyControls);
    }

    #[Test]
    public function validateTestCoverage_missingNonKeyControls_returnsWarning(): void
    {
        // Test all key controls, but skip some non-key controls
        $testedControls = array_filter(
            ControlArea::cases(),
            fn (ControlArea $c) => $c->isKeyControl(),
        );

        $result = $this->rule->validateTestCoverage($testedControls);

        $this->assertTrue($result->passed);
        $this->assertTrue($result->isWarning);
        $this->assertNotEmpty($result->missingControls);
    }

    #[Test]
    public function validateTestCoverage_excludeItGeneralControls_respectsFlag(): void
    {
        $testedControls = array_filter(
            ControlArea::cases(),
            fn (ControlArea $c) => $c !== ControlArea::IT_GENERAL_CONTROLS,
        );

        $result = $this->rule->validateTestCoverage($testedControls, includeItGeneralControls: false);

        $this->assertTrue($result->passed);
    }

    #[Test]
    #[DataProvider('controlAreasProvider')]
    public function validateSampleSize_respectsKeyControlStatus(
        ControlArea $controlArea,
        bool $isKeyControl,
    ): void {
        $this->assertEquals($isKeyControl, $controlArea->isKeyControl());
    }

    /**
     * @return array<string, array{ControlArea, bool}>
     */
    public static function controlAreasProvider(): array
    {
        return [
            'THREE_WAY_MATCH' => [ControlArea::THREE_WAY_MATCH, true],
            'PURCHASE_ORDER_APPROVAL' => [ControlArea::PURCHASE_ORDER_APPROVAL, true],
            'PAYMENT_AUTHORIZATION' => [ControlArea::PAYMENT_AUTHORIZATION, true],
            'SEGREGATION_OF_DUTIES' => [ControlArea::SEGREGATION_OF_DUTIES, true],
            'VENDOR_MASTER_DATA' => [ControlArea::VENDOR_MASTER_DATA, false],
            'VENDOR_CORRESPONDENCE' => [ControlArea::VENDOR_CORRESPONDENCE, false],
        ];
    }

    #[Test]
    public function resultToArray_containsRelevantFields(): void
    {
        $result = ControlTestingRuleResult::fail(
            message: 'Test failure',
            reason: 'INSUFFICIENT_SAMPLE_SIZE',
            controlArea: 'THREE_WAY_MATCH',
            requiredSampleSize: 45,
            proposedSampleSize: 20,
            populationSize: 500,
        );

        $array = $result->toArray();

        $this->assertArrayHasKey('passed', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('reason', $array);
        $this->assertArrayHasKey('control_area', $array);
        $this->assertArrayHasKey('required_sample_size', $array);
    }
}
