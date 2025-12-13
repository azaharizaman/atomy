<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\SOX;

use Nexus\ProcurementOperations\DTOs\SOX\SOXControlValidationResult;
use Nexus\ProcurementOperations\Enums\SOXControlPoint;
use Nexus\ProcurementOperations\Enums\SOXControlResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SOXControlValidationResult::class)]
final class SOXControlValidationResultTest extends TestCase
{
    #[Test]
    public function passed_factory_creates_passed_result(): void
    {
        $result = SOXControlValidationResult::passed(
            controlPoint: SOXControlPoint::REQ_BUDGET_CHECK,
            message: 'Budget check passed',
            durationMs: 45.5,
        );

        $this->assertEquals(SOXControlResult::PASSED, $result->result);
        $this->assertEquals(SOXControlPoint::REQ_BUDGET_CHECK, $result->controlPoint);
        $this->assertEquals('Budget check passed', $result->message);
        $this->assertEquals(45.5, $result->durationMs);
        $this->assertTrue($result->allowsProceeding());
    }

    #[Test]
    public function failed_factory_creates_failed_result(): void
    {
        $result = SOXControlValidationResult::failed(
            controlPoint: SOXControlPoint::REQ_BUDGET_CHECK,
            message: 'Budget exceeded',
            durationMs: 50.0,
            details: ['budget_available' => 1000, 'requested' => 5000],
        );

        $this->assertEquals(SOXControlResult::FAILED, $result->result);
        $this->assertFalse($result->allowsProceeding());
        $this->assertArrayHasKey('budget_available', $result->details);
    }

    #[Test]
    public function skipped_factory_creates_skipped_result(): void
    {
        $result = SOXControlValidationResult::skipped(
            controlPoint: SOXControlPoint::REQ_BUDGET_CHECK,
            reason: 'SOX compliance disabled for tenant',
        );

        $this->assertEquals(SOXControlResult::SKIPPED, $result->result);
        $this->assertTrue($result->allowsProceeding());
    }

    #[Test]
    public function overridden_factory_creates_overridden_result(): void
    {
        $result = SOXControlValidationResult::overridden(
            controlPoint: SOXControlPoint::REQ_BUDGET_CHECK,
            overrideId: 'override-001',
            approvedBy: 'manager-001',
        );

        $this->assertEquals(SOXControlResult::OVERRIDDEN, $result->result);
        $this->assertTrue($result->allowsProceeding());
        $this->assertEquals('override-001', $result->overrideId);
        $this->assertEquals('manager-001', $result->approvedBy);
    }

    #[Test]
    public function error_factory_creates_error_result(): void
    {
        $result = SOXControlValidationResult::error(
            controlPoint: SOXControlPoint::REQ_BUDGET_CHECK,
            errorMessage: 'Database connection failed',
        );

        $this->assertEquals(SOXControlResult::ERROR, $result->result);
        $this->assertFalse($result->allowsProceeding());
        $this->assertStringContainsString('Database', $result->message);
    }

    #[Test]
    public function timeout_factory_creates_timeout_result(): void
    {
        $result = SOXControlValidationResult::timeout(
            controlPoint: SOXControlPoint::REQ_BUDGET_CHECK,
            timeoutMs: 200.0,
        );

        $this->assertEquals(SOXControlResult::TIMEOUT, $result->result);
        $this->assertFalse($result->allowsProceeding());
        $this->assertEquals(200.0, $result->durationMs);
    }

    #[Test]
    #[DataProvider('resultAllowsProceedingProvider')]
    public function allowsProceeding_returns_correct_value(
        SOXControlResult $result,
        bool $expectedAllowsProceeding,
    ): void {
        $validationResult = new SOXControlValidationResult(
            controlPoint: SOXControlPoint::REQ_BUDGET_CHECK,
            result: $result,
            message: 'Test',
            durationMs: 50.0,
        );

        $this->assertEquals($expectedAllowsProceeding, $validationResult->allowsProceeding());
    }

    /**
     * @return iterable<array{SOXControlResult, bool}>
     */
    public static function resultAllowsProceedingProvider(): iterable
    {
        yield 'PASSED allows proceeding' => [SOXControlResult::PASSED, true];
        yield 'FAILED blocks proceeding' => [SOXControlResult::FAILED, false];
        yield 'SKIPPED allows proceeding' => [SOXControlResult::SKIPPED, true];
        yield 'PENDING_REVIEW blocks proceeding' => [SOXControlResult::PENDING_REVIEW, false];
        yield 'OVERRIDDEN allows proceeding' => [SOXControlResult::OVERRIDDEN, true];
        yield 'ERROR blocks proceeding' => [SOXControlResult::ERROR, false];
        yield 'TIMEOUT blocks proceeding' => [SOXControlResult::TIMEOUT, false];
    }

    #[Test]
    public function requiresInvestigation_returns_true_for_failures(): void
    {
        $failedResult = SOXControlValidationResult::failed(
            controlPoint: SOXControlPoint::PAY_DUAL_APPROVAL,
            message: 'Dual approval missing',
            durationMs: 30.0,
        );

        $this->assertTrue($failedResult->requiresInvestigation());
    }

    #[Test]
    public function requiresInvestigation_returns_false_for_passed(): void
    {
        $passedResult = SOXControlValidationResult::passed(
            controlPoint: SOXControlPoint::PAY_DUAL_APPROVAL,
            message: 'Dual approval verified',
            durationMs: 30.0,
        );

        $this->assertFalse($passedResult->requiresInvestigation());
    }

    #[Test]
    public function toArray_returns_serializable_data(): void
    {
        $result = SOXControlValidationResult::passed(
            controlPoint: SOXControlPoint::REQ_BUDGET_CHECK,
            message: 'Budget check passed',
            durationMs: 45.5,
        );

        $array = $result->toArray();

        $this->assertArrayHasKey('control_point', $array);
        $this->assertArrayHasKey('result', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('duration_ms', $array);
        $this->assertEquals(SOXControlPoint::REQ_BUDGET_CHECK->value, $array['control_point']);
        $this->assertEquals(SOXControlResult::PASSED->value, $array['result']);
    }
}
