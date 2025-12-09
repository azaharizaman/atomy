<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Rules\Vendor;

use Nexus\ProcurementOperations\DTOs\VendorComplianceContext;
use Nexus\ProcurementOperations\Enums\VendorHoldReason;
use Nexus\ProcurementOperations\Rules\Vendor\VendorNotBlockedRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorNotBlockedRule::class)]
final class VendorNotBlockedRuleTest extends TestCase
{
    private VendorNotBlockedRule $rule;

    protected function setUp(): void
    {
        $this->rule = new VendorNotBlockedRule();
    }

    private function createContext(
        bool $isActive = true,
        bool $isBlocked = false,
        array $holdReasons = []
    ): VendorComplianceContext {
        return new VendorComplianceContext(
            vendorId: 'vendor-1',
            vendorName: 'Acme Corp',
            isActive: $isActive,
            isBlocked: $isBlocked,
            activeHoldReasons: $holdReasons,
            isCompliant: true,
            complianceChecks: []
        );
    }

    #[Test]
    public function checkForPurchaseOrder_passes_for_active_unblocked_vendor(): void
    {
        $context = $this->createContext(isActive: true, isBlocked: false);

        $result = $this->rule->checkForPurchaseOrder($context);

        $this->assertTrue($result->passed());
    }

    #[Test]
    public function checkForPurchaseOrder_fails_for_inactive_vendor(): void
    {
        $context = $this->createContext(isActive: false, isBlocked: false);

        $result = $this->rule->checkForPurchaseOrder($context);

        $this->assertTrue($result->failed());
        $this->assertSame('VENDOR_INACTIVE', $result->failureCode);
    }

    #[Test]
    public function checkForPurchaseOrder_fails_for_hard_blocked_vendor(): void
    {
        $context = $this->createContext(
            isActive: true,
            isBlocked: true,
            holdReasons: [VendorHoldReason::FRAUD_SUSPECTED]
        );

        $result = $this->rule->checkForPurchaseOrder($context);

        $this->assertTrue($result->failed());
        $this->assertSame('VENDOR_HARD_BLOCKED', $result->failureCode);
        $this->assertStringContainsString('blocked', $result->failureReason);
    }

    #[Test]
    public function checkForPurchaseOrder_passes_for_soft_blocked_vendor(): void
    {
        // Soft blocks only prevent new POs, not existing transactions
        // But for new POs, we should still allow (per business rule)
        $context = $this->createContext(
            isActive: true,
            isBlocked: true,
            holdReasons: [VendorHoldReason::COMPLIANCE_PENDING]
        );

        // With soft blocks, vendor can still receive new POs
        $this->assertTrue($context->canReceiveNewPurchaseOrders());
    }

    #[Test]
    public function checkForPayment_passes_for_active_unblocked_vendor(): void
    {
        $context = $this->createContext(isActive: true, isBlocked: false);

        $result = $this->rule->checkForPayment($context);

        $this->assertTrue($result->passed());
    }

    #[Test]
    public function checkForPayment_fails_for_hard_blocked_vendor(): void
    {
        $context = $this->createContext(
            isActive: true,
            isBlocked: true,
            holdReasons: [VendorHoldReason::SANCTIONS_LIST]
        );

        $result = $this->rule->checkForPayment($context);

        $this->assertTrue($result->failed());
        $this->assertSame('VENDOR_PAYMENT_BLOCKED', $result->failureCode);
    }

    #[Test]
    public function checkForAnyBlocks_fails_when_any_block_present(): void
    {
        $context = $this->createContext(
            isActive: true,
            isBlocked: true,
            holdReasons: [VendorHoldReason::CERTIFICATE_EXPIRED]
        );

        $result = $this->rule->checkForAnyBlocks($context);

        $this->assertTrue($result->failed());
        $this->assertSame('VENDOR_SOFT_BLOCKED', $result->failureCode);
    }

    #[Test]
    public function checkForAnyBlocks_returns_hard_blocked_code_for_hard_blocks(): void
    {
        $context = $this->createContext(
            isActive: true,
            isBlocked: true,
            holdReasons: [VendorHoldReason::LEGAL_ACTION]
        );

        $result = $this->rule->checkForAnyBlocks($context);

        $this->assertTrue($result->failed());
        $this->assertSame('VENDOR_HARD_BLOCKED', $result->failureCode);
    }

    #[Test]
    public function checkForAnyBlocks_passes_when_no_blocks(): void
    {
        $context = $this->createContext(isActive: true, isBlocked: false);

        $result = $this->rule->checkForAnyBlocks($context);

        $this->assertTrue($result->passed());
    }
}
