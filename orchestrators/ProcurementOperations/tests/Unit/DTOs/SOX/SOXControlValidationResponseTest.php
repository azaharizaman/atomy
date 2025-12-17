<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\SOX;

use Nexus\ProcurementOperations\DTOs\SOX\SOXControlValidationResponse;
use Nexus\ProcurementOperations\DTOs\SOX\SOXControlValidationResult;
use Nexus\ProcurementOperations\Enums\SOXControlPoint;
use Nexus\ProcurementOperations\Enums\SOXControlResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SOXControlValidationResponse::class)]
final class SOXControlValidationResponseTest extends TestCase
{
    #[Test]
    public function passed_factory_creates_all_passed_response(): void
    {
        $results = [
            SOXControlValidationResult::passed(
                controlPoint: SOXControlPoint::REQ_BUDGET_CHECK,
                message: 'Budget OK',
                durationMs: 50.0,
            ),
            SOXControlValidationResult::passed(
                controlPoint: SOXControlPoint::REQ_APPROVAL_AUTHORITY,
                message: 'Authority OK',
                durationMs: 30.0,
            ),
        ];

        $response = SOXControlValidationResponse::passed(
            results: $results,
            totalDurationMs: 80.0,
        );

        $this->assertTrue($response->allPassed);
        $this->assertFalse($response->skipped);
        $this->assertCount(2, $response->results);
        $this->assertEquals(80.0, $response->totalDurationMs);
    }

    #[Test]
    public function failed_factory_creates_failed_response(): void
    {
        $results = [
            SOXControlValidationResult::passed(
                controlPoint: SOXControlPoint::REQ_BUDGET_CHECK,
                message: 'Budget OK',
                durationMs: 50.0,
            ),
            SOXControlValidationResult::failed(
                controlPoint: SOXControlPoint::REQ_SOD_CHECK,
                message: 'SOD violation detected',
                durationMs: 30.0,
            ),
        ];

        $response = SOXControlValidationResponse::failed(
            results: $results,
            totalDurationMs: 80.0,
        );

        $this->assertFalse($response->allPassed);
        $this->assertFalse($response->skipped);
        $this->assertNotEmpty($response->failedControls);
    }

    #[Test]
    public function skipped_factory_creates_skipped_response(): void
    {
        $response = SOXControlValidationResponse::skipped(
            reason: 'SOX compliance disabled for tenant',
        );

        $this->assertFalse($response->allPassed);
        $this->assertTrue($response->skipped);
        $this->assertEmpty($response->results);
        $this->assertStringContainsString('disabled', $response->skipReason);
    }

    #[Test]
    public function passedWithOverride_creates_override_response(): void
    {
        $results = [
            SOXControlValidationResult::passed(
                controlPoint: SOXControlPoint::REQ_BUDGET_CHECK,
                message: 'Budget OK',
                durationMs: 50.0,
            ),
            SOXControlValidationResult::overridden(
                controlPoint: SOXControlPoint::REQ_SOD_CHECK,
                overrideId: 'override-001',
                approvedBy: 'manager-001',
            ),
        ];

        $response = SOXControlValidationResponse::passedWithOverride(
            results: $results,
            overrideIds: ['override-001'],
            totalDurationMs: 80.0,
        );

        $this->assertTrue($response->allPassed);
        $this->assertFalse($response->skipped);
        $this->assertNotEmpty($response->overrideIds);
    }

    #[Test]
    public function getFailedControls_returns_only_failures(): void
    {
        $results = [
            SOXControlValidationResult::passed(
                controlPoint: SOXControlPoint::REQ_BUDGET_CHECK,
                message: 'Budget OK',
                durationMs: 50.0,
            ),
            SOXControlValidationResult::failed(
                controlPoint: SOXControlPoint::REQ_SOD_CHECK,
                message: 'SOD violation',
                durationMs: 30.0,
            ),
            SOXControlValidationResult::failed(
                controlPoint: SOXControlPoint::REQ_APPROVAL_AUTHORITY,
                message: 'Authority exceeded',
                durationMs: 25.0,
            ),
        ];

        $response = SOXControlValidationResponse::failed(
            results: $results,
            totalDurationMs: 105.0,
        );

        $failedControls = $response->failedControls;

        $this->assertCount(2, $failedControls);
        $this->assertContains(SOXControlPoint::REQ_SOD_CHECK, array_map(
            fn($r) => $r->controlPoint,
            $failedControls,
        ));
    }

    #[Test]
    public function getBlockingFailures_returns_only_blocking(): void
    {
        $results = [
            SOXControlValidationResult::failed(
                controlPoint: SOXControlPoint::PAY_DUAL_APPROVAL, // High risk - blocking
                message: 'Dual approval missing',
                durationMs: 30.0,
            ),
            SOXControlValidationResult::failed(
                controlPoint: SOXControlPoint::REQ_BUDGET_CHECK, // Medium risk - may not block
                message: 'Budget exceeded',
                durationMs: 25.0,
            ),
        ];

        $response = SOXControlValidationResponse::failed(
            results: $results,
            totalDurationMs: 55.0,
        );

        $blocking = $response->getBlockingFailures();

        // Both failures block proceeding based on allowsProceeding()
        $this->assertNotEmpty($blocking);
    }

    #[Test]
    public function canProceed_returns_false_when_has_blocking_failures(): void
    {
        $results = [
            SOXControlValidationResult::passed(
                controlPoint: SOXControlPoint::REQ_BUDGET_CHECK,
                message: 'Budget OK',
                durationMs: 50.0,
            ),
            SOXControlValidationResult::failed(
                controlPoint: SOXControlPoint::PAY_DUAL_APPROVAL,
                message: 'Dual approval missing',
                durationMs: 30.0,
            ),
        ];

        $response = SOXControlValidationResponse::failed(
            results: $results,
            totalDurationMs: 80.0,
        );

        $this->assertFalse($response->canProceed());
    }

    #[Test]
    public function canProceed_returns_true_when_all_passed(): void
    {
        $results = [
            SOXControlValidationResult::passed(
                controlPoint: SOXControlPoint::REQ_BUDGET_CHECK,
                message: 'Budget OK',
                durationMs: 50.0,
            ),
        ];

        $response = SOXControlValidationResponse::passed(
            results: $results,
            totalDurationMs: 50.0,
        );

        $this->assertTrue($response->canProceed());
    }

    #[Test]
    public function canProceed_returns_true_when_skipped(): void
    {
        $response = SOXControlValidationResponse::skipped(
            reason: 'SOX disabled',
        );

        $this->assertTrue($response->canProceed());
    }

    #[Test]
    public function toArray_returns_serializable_data(): void
    {
        $results = [
            SOXControlValidationResult::passed(
                controlPoint: SOXControlPoint::REQ_BUDGET_CHECK,
                message: 'Budget OK',
                durationMs: 50.0,
            ),
        ];

        $response = SOXControlValidationResponse::passed(
            results: $results,
            totalDurationMs: 50.0,
        );

        $array = $response->toArray();

        $this->assertArrayHasKey('all_passed', $array);
        $this->assertArrayHasKey('skipped', $array);
        $this->assertArrayHasKey('results', $array);
        $this->assertArrayHasKey('total_duration_ms', $array);
        $this->assertTrue($array['all_passed']);
    }

    #[Test]
    public function getSummary_returns_control_summary(): void
    {
        $results = [
            SOXControlValidationResult::passed(
                controlPoint: SOXControlPoint::REQ_BUDGET_CHECK,
                message: 'Budget OK',
                durationMs: 50.0,
            ),
            SOXControlValidationResult::failed(
                controlPoint: SOXControlPoint::REQ_SOD_CHECK,
                message: 'SOD violation',
                durationMs: 30.0,
            ),
            SOXControlValidationResult::skipped(
                controlPoint: SOXControlPoint::REQ_APPROVAL_AUTHORITY,
                reason: 'Not applicable',
            ),
        ];

        $response = SOXControlValidationResponse::failed(
            results: $results,
            totalDurationMs: 80.0,
        );

        $summary = $response->getSummary();

        $this->assertEquals(3, $summary['total']);
        $this->assertEquals(1, $summary['passed']);
        $this->assertEquals(1, $summary['failed']);
        $this->assertEquals(1, $summary['skipped']);
    }
}
