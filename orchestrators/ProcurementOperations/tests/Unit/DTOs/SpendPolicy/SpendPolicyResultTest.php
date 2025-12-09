<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\SpendPolicy;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyResult;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyViolation;
use Nexus\ProcurementOperations\Enums\PolicyAction;
use Nexus\ProcurementOperations\Enums\PolicyViolationSeverity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SpendPolicyResult::class)]
final class SpendPolicyResultTest extends TestCase
{
    public function test_can_create_passing_result(): void
    {
        $result = SpendPolicyResult::pass(['policy1', 'policy2']);

        $this->assertTrue($result->passed);
        $this->assertEquals(PolicyAction::ALLOW, $result->recommendedAction);
        $this->assertEmpty($result->violations);
        $this->assertEquals(['policy1', 'policy2'], $result->passedPolicies);
    }

    public function test_can_create_failing_result(): void
    {
        $violations = [
            SpendPolicyViolation::categoryLimitExceeded(
                threshold: Money::of(1000.00, 'MYR'),
                actual: Money::of(1500.00, 'MYR'),
                categoryId: 'cat-1',
            ),
        ];

        $result = SpendPolicyResult::fail(
            violations: $violations,
            recommendedAction: PolicyAction::REQUIRE_APPROVAL,
            passedPolicies: ['policy1']
        );

        $this->assertFalse($result->passed);
        $this->assertEquals(PolicyAction::REQUIRE_APPROVAL, $result->recommendedAction);
        $this->assertCount(1, $result->violations);
        $this->assertEquals(['policy1'], $result->passedPolicies);
    }

    public function test_has_violations_returns_true_when_violations_exist(): void
    {
        $violations = [
            SpendPolicyViolation::categoryLimitExceeded(
                threshold: Money::of(1000.00, 'MYR'),
                actual: Money::of(1500.00, 'MYR'),
                categoryId: 'cat-1',
            ),
        ];

        $result = SpendPolicyResult::fail($violations);

        $this->assertTrue($result->hasViolations());
    }

    public function test_has_violations_returns_false_when_no_violations(): void
    {
        $result = SpendPolicyResult::pass();

        $this->assertFalse($result->hasViolations());
    }

    public function test_get_violation_count_returns_correct_count(): void
    {
        $violations = [
            SpendPolicyViolation::categoryLimitExceeded(
                threshold: Money::of(1000.00, 'MYR'),
                actual: Money::of(1500.00, 'MYR'),
                categoryId: 'cat-1',
            ),
            SpendPolicyViolation::vendorLimitExceeded(
                threshold: Money::of(500.00, 'MYR'),
                actual: Money::of(700.00, 'MYR'),
                vendorId: 'vendor-1',
            ),
        ];

        $result = SpendPolicyResult::fail($violations);

        $this->assertEquals(2, $result->getViolationCount());
    }

    public function test_get_overridable_violations_filters_correctly(): void
    {
        $violations = [
            // Overridable
            SpendPolicyViolation::categoryLimitExceeded(
                threshold: Money::of(1000.00, 'MYR'),
                actual: Money::of(1500.00, 'MYR'),
                categoryId: 'cat-1',
            ),
            // Not overridable
            SpendPolicyViolation::contractComplianceRequired(
                message: 'Contract required',
                contractId: 'contract-1',
            ),
        ];

        $result = SpendPolicyResult::fail($violations);
        $overridable = $result->getOverridableViolations();

        $this->assertCount(1, $overridable);
        $this->assertTrue(reset($overridable)->isOverridable);
    }

    public function test_get_blocking_violations_filters_correctly(): void
    {
        $violations = [
            // WARNING - not blocking
            SpendPolicyViolation::maverickSpendDetected(
                message: 'No contract found',
                severity: PolicyViolationSeverity::WARNING,
            ),
            // ERROR - blocking
            SpendPolicyViolation::categoryLimitExceeded(
                threshold: Money::of(1000.00, 'MYR'),
                actual: Money::of(1500.00, 'MYR'),
                categoryId: 'cat-1',
                severity: PolicyViolationSeverity::ERROR,
            ),
            // CRITICAL - blocking
            SpendPolicyViolation::budgetUnavailable(
                budgetRemaining: Money::of(100.00, 'MYR'),
                requested: Money::of(200.00, 'MYR'),
                budgetId: 'budget-1',
                severity: PolicyViolationSeverity::CRITICAL,
            ),
        ];

        $result = SpendPolicyResult::fail($violations);
        $blocking = $result->getBlockingViolations();

        $this->assertCount(2, $blocking);
        foreach ($blocking as $violation) {
            $this->assertTrue($violation->severity->isBlocking());
        }
    }

    /**
     * Test case 1: No blocking violations → should return true
     */
    public function test_can_proceed_with_override_returns_true_when_no_blocking_violations(): void
    {
        $violations = [
            // WARNING severity - not blocking
            SpendPolicyViolation::maverickSpendDetected(
                message: 'No contract found',
                severity: PolicyViolationSeverity::WARNING,
            ),
            // INFO severity - not blocking
            SpendPolicyViolation::preferredVendorRequired(
                categoryId: 'cat-1',
                severity: PolicyViolationSeverity::INFO,
            ),
        ];

        $result = SpendPolicyResult::fail($violations);

        $this->assertTrue($result->canProceedWithOverride());
    }

    /**
     * Test case 2: All blocking violations are overridable → should return true
     */
    public function test_can_proceed_with_override_returns_true_when_all_blocking_violations_are_overridable(): void
    {
        $violations = [
            // ERROR severity, overridable
            SpendPolicyViolation::categoryLimitExceeded(
                threshold: Money::of(1000.00, 'MYR'),
                actual: Money::of(1500.00, 'MYR'),
                categoryId: 'cat-1',
                severity: PolicyViolationSeverity::ERROR,
            ),
            // ERROR severity, overridable
            SpendPolicyViolation::vendorLimitExceeded(
                threshold: Money::of(500.00, 'MYR'),
                actual: Money::of(700.00, 'MYR'),
                vendorId: 'vendor-1',
                severity: PolicyViolationSeverity::ERROR,
            ),
        ];

        $result = SpendPolicyResult::fail($violations);

        $this->assertTrue($result->canProceedWithOverride());
    }

    /**
     * Test case 3: At least one blocking violation is NOT overridable → should return false
     */
    public function test_can_proceed_with_override_returns_false_when_any_blocking_violation_is_not_overridable(): void
    {
        $violations = [
            // CRITICAL severity, NOT overridable
            SpendPolicyViolation::budgetUnavailable(
                budgetRemaining: Money::of(100.00, 'MYR'),
                requested: Money::of(200.00, 'MYR'),
                budgetId: 'budget-1',
                severity: PolicyViolationSeverity::CRITICAL,
            ),
        ];

        $result = SpendPolicyResult::fail($violations);

        $this->assertFalse($result->canProceedWithOverride());
    }

    /**
     * Test case 4: Mixed overridable and non-overridable violations → should return false
     * This is the critical security test - ensures non-overridable violations cannot be bypassed
     */
    public function test_can_proceed_with_override_returns_false_with_mixed_overridable_and_non_overridable_blocking_violations(): void
    {
        $violations = [
            // ERROR severity, overridable (blocking)
            SpendPolicyViolation::categoryLimitExceeded(
                threshold: Money::of(1000.00, 'MYR'),
                actual: Money::of(1500.00, 'MYR'),
                categoryId: 'cat-1',
                severity: PolicyViolationSeverity::ERROR,
            ),
            // ERROR severity, NOT overridable (blocking)
            SpendPolicyViolation::contractComplianceRequired(
                message: 'Contract required',
                contractId: 'contract-1',
                severity: PolicyViolationSeverity::ERROR,
            ),
            // CRITICAL severity, NOT overridable (blocking)
            SpendPolicyViolation::budgetUnavailable(
                budgetRemaining: Money::of(100.00, 'MYR'),
                requested: Money::of(200.00, 'MYR'),
                budgetId: 'budget-1',
                severity: PolicyViolationSeverity::CRITICAL,
            ),
        ];

        $result = SpendPolicyResult::fail($violations);

        // Should return false because there are non-overridable blocking violations
        // This prevents security bypass where users could override even when critical
        // controls (budget, contract compliance) are violated
        $this->assertFalse($result->canProceedWithOverride());
    }

    /**
     * Additional test: Mix of blocking and non-blocking violations, only blocking ones matter
     */
    public function test_can_proceed_with_override_ignores_non_blocking_violations(): void
    {
        $violations = [
            // WARNING severity - not blocking, not overridable doesn't matter
            SpendPolicyViolation::maverickSpendDetected(
                message: 'No contract found',
                severity: PolicyViolationSeverity::WARNING,
            ),
            // ERROR severity - blocking, overridable
            SpendPolicyViolation::categoryLimitExceeded(
                threshold: Money::of(1000.00, 'MYR'),
                actual: Money::of(1500.00, 'MYR'),
                categoryId: 'cat-1',
                severity: PolicyViolationSeverity::ERROR,
            ),
        ];

        $result = SpendPolicyResult::fail($violations);

        // Should return true because the only blocking violation is overridable
        $this->assertTrue($result->canProceedWithOverride());
    }

    /**
     * Edge case: Empty violations array
     */
    public function test_can_proceed_with_override_returns_true_when_no_violations(): void
    {
        $result = SpendPolicyResult::pass();

        $this->assertTrue($result->canProceedWithOverride());
    }
}
