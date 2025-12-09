<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Rules\Vendor;

use Nexus\ProcurementOperations\DTOs\VendorComplianceContext;
use Nexus\ProcurementOperations\Rules\Vendor\VendorCompliantRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorCompliantRule::class)]
final class VendorCompliantRuleTest extends TestCase
{
    private function createContext(
        bool $isCompliant = true,
        array $complianceChecks = [],
        ?\DateTimeImmutable $lastReview = null
    ): VendorComplianceContext {
        return new VendorComplianceContext(
            vendorId: 'vendor-1',
            vendorName: 'Acme Corp',
            isActive: true,
            isBlocked: false,
            activeHoldReasons: [],
            isCompliant: $isCompliant,
            complianceChecks: $complianceChecks,
            lastComplianceReview: $lastReview
        );
    }

    #[Test]
    public function check_passes_for_compliant_vendor(): void
    {
        $rule = new VendorCompliantRule();
        $context = $this->createContext(isCompliant: true);

        $result = $rule->check($context);

        $this->assertTrue($result->passed());
    }

    #[Test]
    public function check_fails_for_non_compliant_vendor(): void
    {
        $rule = new VendorCompliantRule();
        $context = $this->createContext(
            isCompliant: false,
            complianceChecks: [
                'business_license_valid' => false,
                'insurance_current' => true,
            ]
        );

        $result = $rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertSame('VENDOR_NON_COMPLIANT', $result->failureCode);
    }

    #[Test]
    public function check_validates_required_checks(): void
    {
        $rule = new VendorCompliantRule(requiredChecks: ['special_certification']);
        $context = $this->createContext(
            isCompliant: true,
            complianceChecks: [
                'business_license_valid' => true,
                'special_certification' => false, // Required but failed
            ]
        );

        $result = $rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertSame('VENDOR_MISSING_COMPLIANCE_CHECKS', $result->failureCode);
        $this->assertStringContainsString('special_certification', $result->failureReason);
    }

    #[Test]
    public function checkForHighValue_fails_when_no_review(): void
    {
        $rule = new VendorCompliantRule();
        $context = $this->createContext(
            isCompliant: true,
            lastReview: null
        );

        $result = $rule->checkForHighValue($context);

        $this->assertTrue($result->failed());
        $this->assertSame('VENDOR_NO_COMPLIANCE_REVIEW', $result->failureCode);
    }

    #[Test]
    public function checkForHighValue_fails_when_review_outdated(): void
    {
        $rule = new VendorCompliantRule();
        $context = $this->createContext(
            isCompliant: true,
            lastReview: new \DateTimeImmutable('-400 days') // Over 365 days old
        );

        $result = $rule->checkForHighValue($context);

        $this->assertTrue($result->failed());
        $this->assertSame('VENDOR_COMPLIANCE_REVIEW_OUTDATED', $result->failureCode);
    }

    #[Test]
    public function checkForHighValue_passes_with_recent_review(): void
    {
        $rule = new VendorCompliantRule();
        $context = $this->createContext(
            isCompliant: true,
            lastReview: new \DateTimeImmutable('-30 days')
        );

        $result = $rule->checkForHighValue($context);

        $this->assertTrue($result->passed());
    }
}
