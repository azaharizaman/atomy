<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\SpendPolicy;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\SpendPolicy\SpendPolicyViolation;
use Nexus\ProcurementOperations\Enums\PolicyViolationSeverity;
use Nexus\ProcurementOperations\Enums\SpendPolicyType;
use PHPUnit\Framework\TestCase;

final class SpendPolicyViolationTest extends TestCase
{
    public function test_can_create_violation_with_all_properties(): void
    {
        $threshold = Money::of(1000.00, 'MYR');
        $actual = Money::of(1500.00, 'MYR');

        $violation = new SpendPolicyViolation(
            policyType: SpendPolicyType::CATEGORY_LIMIT,
            severity: PolicyViolationSeverity::ERROR,
            message: 'Test violation',
            ruleCode: 'TEST_CODE',
            isOverridable: true,
            threshold: $threshold,
            actual: $actual,
            relatedEntityId: 'entity-1',
            context: ['key' => 'value'],
        );

        $this->assertEquals(SpendPolicyType::CATEGORY_LIMIT, $violation->policyType);
        $this->assertEquals(PolicyViolationSeverity::ERROR, $violation->severity);
        $this->assertEquals('Test violation', $violation->message);
        $this->assertEquals('TEST_CODE', $violation->ruleCode);
        $this->assertTrue($violation->isOverridable);
        $this->assertEquals($threshold, $violation->threshold);
        $this->assertEquals($actual, $violation->actual);
        $this->assertEquals('entity-1', $violation->relatedEntityId);
        $this->assertEquals(['key' => 'value'], $violation->context);
    }

    public function test_category_limit_exceeded_creates_overridable_violation(): void
    {
        $threshold = Money::of(1000.00, 'MYR');
        $actual = Money::of(1500.00, 'MYR');

        $violation = SpendPolicyViolation::categoryLimitExceeded(
            threshold: $threshold,
            actual: $actual,
            categoryId: 'cat-123',
        );

        $this->assertEquals(SpendPolicyType::CATEGORY_LIMIT, $violation->policyType);
        $this->assertEquals(PolicyViolationSeverity::ERROR, $violation->severity);
        $this->assertTrue($violation->isOverridable);
        $this->assertEquals('SPEND_CATEGORY_LIMIT_EXCEEDED', $violation->ruleCode);
        $this->assertEquals($threshold, $violation->threshold);
        $this->assertEquals($actual, $violation->actual);
        $this->assertEquals('cat-123', $violation->relatedEntityId);
        $this->assertStringContainsString('Category spend limit exceeded', $violation->message);
        $this->assertStringContainsString($threshold->format(), $violation->message);
        $this->assertStringContainsString($actual->format(), $violation->message);
    }

    public function test_category_limit_exceeded_respects_custom_severity(): void
    {
        $violation = SpendPolicyViolation::categoryLimitExceeded(
            threshold: Money::of(1000.00, 'MYR'),
            actual: Money::of(1500.00, 'MYR'),
            categoryId: 'cat-123',
            severity: PolicyViolationSeverity::WARNING,
        );

        $this->assertEquals(PolicyViolationSeverity::WARNING, $violation->severity);
    }

    public function test_vendor_limit_exceeded_creates_overridable_violation(): void
    {
        $threshold = Money::of(500.00, 'MYR');
        $actual = Money::of(700.00, 'MYR');

        $violation = SpendPolicyViolation::vendorLimitExceeded(
            threshold: $threshold,
            actual: $actual,
            vendorId: 'vendor-456',
        );

        $this->assertEquals(SpendPolicyType::VENDOR_LIMIT, $violation->policyType);
        $this->assertEquals(PolicyViolationSeverity::ERROR, $violation->severity);
        $this->assertTrue($violation->isOverridable);
        $this->assertEquals('SPEND_VENDOR_LIMIT_EXCEEDED', $violation->ruleCode);
        $this->assertEquals($threshold, $violation->threshold);
        $this->assertEquals($actual, $violation->actual);
        $this->assertEquals('vendor-456', $violation->relatedEntityId);
        $this->assertStringContainsString('Vendor spend limit exceeded', $violation->message);
        $this->assertStringContainsString($threshold->format(), $violation->message);
        $this->assertStringContainsString($actual->format(), $violation->message);
    }

    public function test_vendor_limit_exceeded_respects_custom_severity(): void
    {
        $violation = SpendPolicyViolation::vendorLimitExceeded(
            threshold: Money::of(500.00, 'MYR'),
            actual: Money::of(700.00, 'MYR'),
            vendorId: 'vendor-456',
            severity: PolicyViolationSeverity::CRITICAL,
        );

        $this->assertEquals(PolicyViolationSeverity::CRITICAL, $violation->severity);
    }

    public function test_maverick_spend_detected_creates_overridable_violation(): void
    {
        $violation = SpendPolicyViolation::maverickSpendDetected(
            message: 'Purchase without contract',
            contractId: 'contract-789',
        );

        $this->assertEquals(SpendPolicyType::MAVERICK_SPEND, $violation->policyType);
        $this->assertEquals(PolicyViolationSeverity::WARNING, $violation->severity);
        $this->assertTrue($violation->isOverridable);
        $this->assertEquals('SPEND_MAVERICK_DETECTED', $violation->ruleCode);
        $this->assertEquals('Purchase without contract', $violation->message);
        $this->assertEquals('contract-789', $violation->relatedEntityId);
    }

    public function test_maverick_spend_detected_respects_custom_severity(): void
    {
        $violation = SpendPolicyViolation::maverickSpendDetected(
            message: 'Purchase without contract',
            severity: PolicyViolationSeverity::ERROR,
        );

        $this->assertEquals(PolicyViolationSeverity::ERROR, $violation->severity);
    }

    public function test_maverick_spend_detected_works_without_contract_id(): void
    {
        $violation = SpendPolicyViolation::maverickSpendDetected(
            message: 'Purchase without contract',
        );

        $this->assertNull($violation->relatedEntityId);
    }

    public function test_preferred_vendor_required_creates_overridable_violation(): void
    {
        $violation = SpendPolicyViolation::preferredVendorRequired(
            categoryId: 'cat-999',
        );

        $this->assertEquals(SpendPolicyType::PREFERRED_VENDOR, $violation->policyType);
        $this->assertEquals(PolicyViolationSeverity::WARNING, $violation->severity);
        $this->assertTrue($violation->isOverridable);
        $this->assertEquals('SPEND_PREFERRED_VENDOR_REQUIRED', $violation->ruleCode);
        $this->assertEquals('A preferred vendor is required for this category', $violation->message);
        $this->assertEquals('cat-999', $violation->relatedEntityId);
    }

    public function test_preferred_vendor_required_respects_custom_severity(): void
    {
        $violation = SpendPolicyViolation::preferredVendorRequired(
            categoryId: 'cat-999',
            severity: PolicyViolationSeverity::ERROR,
        );

        $this->assertEquals(PolicyViolationSeverity::ERROR, $violation->severity);
    }

    public function test_contract_compliance_required_creates_non_overridable_violation(): void
    {
        $violation = SpendPolicyViolation::contractComplianceRequired(
            message: 'Contract terms not met',
            contractId: 'contract-abc',
        );

        $this->assertEquals(SpendPolicyType::CONTRACT_COMPLIANCE, $violation->policyType);
        $this->assertEquals(PolicyViolationSeverity::ERROR, $violation->severity);
        $this->assertFalse($violation->isOverridable); // Critical: NOT overridable
        $this->assertEquals('SPEND_CONTRACT_COMPLIANCE_REQUIRED', $violation->ruleCode);
        $this->assertEquals('Contract terms not met', $violation->message);
        $this->assertEquals('contract-abc', $violation->relatedEntityId);
    }

    public function test_contract_compliance_required_respects_custom_severity(): void
    {
        $violation = SpendPolicyViolation::contractComplianceRequired(
            message: 'Contract terms not met',
            contractId: 'contract-abc',
            severity: PolicyViolationSeverity::CRITICAL,
        );

        $this->assertEquals(PolicyViolationSeverity::CRITICAL, $violation->severity);
    }

    public function test_budget_unavailable_creates_non_overridable_violation(): void
    {
        $budgetRemaining = Money::of(100.00, 'MYR');
        $requested = Money::of(200.00, 'MYR');

        $violation = SpendPolicyViolation::budgetUnavailable(
            budgetRemaining: $budgetRemaining,
            requested: $requested,
            budgetId: 'budget-xyz',
        );

        $this->assertEquals(SpendPolicyType::BUDGET_AVAILABILITY, $violation->policyType);
        $this->assertEquals(PolicyViolationSeverity::CRITICAL, $violation->severity);
        $this->assertFalse($violation->isOverridable); // Critical: NOT overridable
        $this->assertEquals('SPEND_BUDGET_UNAVAILABLE', $violation->ruleCode);
        $this->assertEquals($budgetRemaining, $violation->threshold);
        $this->assertEquals($requested, $violation->actual);
        $this->assertEquals('budget-xyz', $violation->relatedEntityId);
        $this->assertStringContainsString('Insufficient budget', $violation->message);
        $this->assertStringContainsString($budgetRemaining->format(), $violation->message);
        $this->assertStringContainsString($requested->format(), $violation->message);
    }

    public function test_budget_unavailable_respects_custom_severity(): void
    {
        $violation = SpendPolicyViolation::budgetUnavailable(
            budgetRemaining: Money::of(100.00, 'MYR'),
            requested: Money::of(200.00, 'MYR'),
            budgetId: 'budget-xyz',
            severity: PolicyViolationSeverity::ERROR,
        );

        $this->assertEquals(PolicyViolationSeverity::ERROR, $violation->severity);
    }

    /**
     * Test the critical security behavior: contract compliance and budget violations
     * are NOT overridable to prevent security bypass
     */
    public function test_security_critical_violations_are_not_overridable(): void
    {
        $contractViolation = SpendPolicyViolation::contractComplianceRequired(
            message: 'Contract required',
            contractId: 'contract-1',
        );

        $budgetViolation = SpendPolicyViolation::budgetUnavailable(
            budgetRemaining: Money::of(100.00, 'MYR'),
            requested: Money::of(200.00, 'MYR'),
            budgetId: 'budget-1',
        );

        $this->assertFalse($contractViolation->isOverridable, 'Contract compliance violations must NOT be overridable');
        $this->assertFalse($budgetViolation->isOverridable, 'Budget unavailable violations must NOT be overridable');
    }

    /**
     * Test that overridable violations are correctly flagged
     */
    public function test_overridable_violations_are_correctly_flagged(): void
    {
        $categoryViolation = SpendPolicyViolation::categoryLimitExceeded(
            threshold: Money::of(1000.00, 'MYR'),
            actual: Money::of(1500.00, 'MYR'),
            categoryId: 'cat-1',
        );

        $vendorViolation = SpendPolicyViolation::vendorLimitExceeded(
            threshold: Money::of(500.00, 'MYR'),
            actual: Money::of(700.00, 'MYR'),
            vendorId: 'vendor-1',
        );

        $maverickViolation = SpendPolicyViolation::maverickSpendDetected(
            message: 'No contract',
        );

        $preferredVendorViolation = SpendPolicyViolation::preferredVendorRequired(
            categoryId: 'cat-1',
        );

        $this->assertTrue($categoryViolation->isOverridable, 'Category limit violations should be overridable');
        $this->assertTrue($vendorViolation->isOverridable, 'Vendor limit violations should be overridable');
        $this->assertTrue($maverickViolation->isOverridable, 'Maverick spend violations should be overridable');
        $this->assertTrue($preferredVendorViolation->isOverridable, 'Preferred vendor violations should be overridable');
    }
}
