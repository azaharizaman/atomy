<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Rules\Vendor;

use Nexus\ProcurementOperations\DTOs\VendorComplianceContext;
use Nexus\ProcurementOperations\Enums\VendorHoldReason;
use Nexus\ProcurementOperations\Rules\Vendor\VendorActiveRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorActiveRule::class)]
final class VendorActiveRuleTest extends TestCase
{
    private VendorActiveRule $rule;

    protected function setUp(): void
    {
        $this->rule = new VendorActiveRule();
    }

    #[Test]
    public function check_passes_for_active_vendor(): void
    {
        $context = new VendorComplianceContext(
            vendorId: 'vendor-1',
            vendorName: 'Acme Corp',
            isActive: true,
            isBlocked: false,
            activeHoldReasons: [],
            isCompliant: true,
            complianceChecks: []
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->passed());
    }

    #[Test]
    public function check_fails_for_inactive_vendor(): void
    {
        $context = new VendorComplianceContext(
            vendorId: 'vendor-1',
            vendorName: 'Acme Corp',
            isActive: false,
            isBlocked: false,
            activeHoldReasons: [],
            isCompliant: true,
            complianceChecks: []
        );

        $result = $this->rule->check($context);

        $this->assertTrue($result->failed());
        $this->assertStringContainsString('not active', $result->failureReason);
        $this->assertSame('VENDOR_INACTIVE', $result->failureCode);
    }
}
