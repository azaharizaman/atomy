<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Enums;

use Nexus\ProcurementOperations\Enums\SOXControlResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SOXControlResult::class)]
final class SOXControlResultTest extends TestCase
{
    #[Test]
    public function all_results_have_valid_values(): void
    {
        $results = SOXControlResult::cases();

        $this->assertNotEmpty($results);
        $this->assertCount(7, $results);

        foreach ($results as $result) {
            $this->assertNotEmpty($result->value);
        }
    }

    #[Test]
    #[DataProvider('allowsProceedingProvider')]
    public function allowsProceeding_returns_expected_value(
        SOXControlResult $result,
        bool $expected,
    ): void {
        $this->assertEquals($expected, $result->allowsProceeding());
    }

    /**
     * @return iterable<array{SOXControlResult, bool}>
     */
    public static function allowsProceedingProvider(): iterable
    {
        yield 'PASSED allows proceeding' => [SOXControlResult::PASSED, true];
        yield 'SKIPPED allows proceeding' => [SOXControlResult::SKIPPED, true];
        yield 'OVERRIDDEN allows proceeding' => [SOXControlResult::OVERRIDDEN, true];
        yield 'FAILED does not allow proceeding' => [SOXControlResult::FAILED, false];
        yield 'PENDING_REVIEW does not allow proceeding' => [SOXControlResult::PENDING_REVIEW, false];
        yield 'ERROR does not allow proceeding' => [SOXControlResult::ERROR, false];
        yield 'TIMEOUT does not allow proceeding' => [SOXControlResult::TIMEOUT, false];
    }

    #[Test]
    #[DataProvider('requiresInvestigationProvider')]
    public function requiresInvestigation_returns_expected_value(
        SOXControlResult $result,
        bool $expected,
    ): void {
        $this->assertEquals($expected, $result->requiresInvestigation());
    }

    /**
     * @return iterable<array{SOXControlResult, bool}>
     */
    public static function requiresInvestigationProvider(): iterable
    {
        yield 'PASSED does not require investigation' => [SOXControlResult::PASSED, false];
        yield 'SKIPPED does not require investigation' => [SOXControlResult::SKIPPED, false];
        yield 'OVERRIDDEN requires investigation' => [SOXControlResult::OVERRIDDEN, true];
        yield 'FAILED requires investigation' => [SOXControlResult::FAILED, true];
        yield 'PENDING_REVIEW requires investigation' => [SOXControlResult::PENDING_REVIEW, true];
        yield 'ERROR requires investigation' => [SOXControlResult::ERROR, true];
        yield 'TIMEOUT requires investigation' => [SOXControlResult::TIMEOUT, true];
    }

    #[Test]
    public function isFailure_returns_true_only_for_failure_states(): void
    {
        $this->assertTrue(SOXControlResult::FAILED->isFailure());
        $this->assertTrue(SOXControlResult::ERROR->isFailure());
        $this->assertTrue(SOXControlResult::TIMEOUT->isFailure());

        $this->assertFalse(SOXControlResult::PASSED->isFailure());
        $this->assertFalse(SOXControlResult::SKIPPED->isFailure());
        $this->assertFalse(SOXControlResult::OVERRIDDEN->isFailure());
        $this->assertFalse(SOXControlResult::PENDING_REVIEW->isFailure());
    }

    #[Test]
    public function isSuccess_returns_true_only_for_success_states(): void
    {
        $this->assertTrue(SOXControlResult::PASSED->isSuccess());
        $this->assertTrue(SOXControlResult::OVERRIDDEN->isSuccess());

        $this->assertFalse(SOXControlResult::FAILED->isSuccess());
        $this->assertFalse(SOXControlResult::SKIPPED->isSuccess());
        $this->assertFalse(SOXControlResult::ERROR->isSuccess());
        $this->assertFalse(SOXControlResult::TIMEOUT->isSuccess());
        $this->assertFalse(SOXControlResult::PENDING_REVIEW->isSuccess());
    }

    #[Test]
    public function getDescription_returns_readable_string(): void
    {
        foreach (SOXControlResult::cases() as $result) {
            $description = $result->getDescription();

            $this->assertNotEmpty($description);
            $this->assertIsString($description);
        }
    }

    #[Test]
    public function getSeverity_returns_valid_level(): void
    {
        $validSeverities = ['info', 'warning', 'error', 'critical'];

        foreach (SOXControlResult::cases() as $result) {
            $severity = $result->getSeverity();

            $this->assertContains($severity, $validSeverities);
        }
    }

    #[Test]
    public function failure_states_have_high_severity(): void
    {
        $failureStates = [
            SOXControlResult::FAILED,
            SOXControlResult::ERROR,
            SOXControlResult::TIMEOUT,
        ];

        foreach ($failureStates as $state) {
            $this->assertContains(
                $state->getSeverity(),
                ['error', 'critical'],
                "{$state->value} should have error or critical severity",
            );
        }
    }

    #[Test]
    public function success_states_have_low_severity(): void
    {
        $successStates = [
            SOXControlResult::PASSED,
            SOXControlResult::SKIPPED,
        ];

        foreach ($successStates as $state) {
            $this->assertEquals(
                'info',
                $state->getSeverity(),
                "{$state->value} should have info severity",
            );
        }
    }
}
