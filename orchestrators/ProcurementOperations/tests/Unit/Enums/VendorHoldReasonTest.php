<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Enums;

use Nexus\ProcurementOperations\Enums\VendorHoldReason;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorHoldReason::class)]
final class VendorHoldReasonTest extends TestCase
{
    #[Test]
    #[DataProvider('hardBlocksProvider')]
    public function hard_blocks_are_identified_correctly(VendorHoldReason $reason): void
    {
        $this->assertTrue($reason->isHardBlock());
        $this->assertFalse($reason->isSoftBlock());
    }

    public static function hardBlocksProvider(): array
    {
        return [
            'fraud' => [VendorHoldReason::FRAUD_SUSPECTED],
            'sanctions' => [VendorHoldReason::SANCTIONS_LIST],
            'legal' => [VendorHoldReason::LEGAL_ACTION],
            'duplicate' => [VendorHoldReason::DUPLICATE_VENDOR],
            'terminated' => [VendorHoldReason::TERMINATED],
        ];
    }

    #[Test]
    #[DataProvider('softBlocksProvider')]
    public function soft_blocks_are_identified_correctly(VendorHoldReason $reason): void
    {
        $this->assertTrue($reason->isSoftBlock());
        $this->assertFalse($reason->isHardBlock());
    }

    public static function softBlocksProvider(): array
    {
        return [
            'compliance' => [VendorHoldReason::COMPLIANCE_PENDING],
            'certificate' => [VendorHoldReason::CERTIFICATE_EXPIRED],
            'insurance' => [VendorHoldReason::INSURANCE_EXPIRED],
            'tax' => [VendorHoldReason::TAX_DOCUMENT_MISSING],
            'bank' => [VendorHoldReason::BANK_VERIFICATION_PENDING],
            'performance' => [VendorHoldReason::PERFORMANCE_ISSUE],
            'credit' => [VendorHoldReason::CREDIT_LIMIT_EXCEEDED],
            'dispute' => [VendorHoldReason::PAYMENT_DISPUTE],
            'quality' => [VendorHoldReason::QUALITY_ISSUE],
        ];
    }

    #[Test]
    #[DataProvider('autoReleasableProvider')]
    public function auto_releasable_reasons_are_identified(VendorHoldReason $reason): void
    {
        $this->assertTrue($reason->isAutoReleasable());
    }

    public static function autoReleasableProvider(): array
    {
        return [
            'certificate' => [VendorHoldReason::CERTIFICATE_EXPIRED],
            'insurance' => [VendorHoldReason::INSURANCE_EXPIRED],
            'tax' => [VendorHoldReason::TAX_DOCUMENT_MISSING],
            'bank' => [VendorHoldReason::BANK_VERIFICATION_PENDING],
            'credit' => [VendorHoldReason::CREDIT_LIMIT_EXCEEDED],
        ];
    }

    #[Test]
    public function description_returns_human_readable_text(): void
    {
        $reason = VendorHoldReason::FRAUD_SUSPECTED;

        $description = $reason->description();

        $this->assertNotEmpty($description);
        $this->assertStringContainsString('fraud', strtolower($description));
    }

    #[Test]
    public function severity_level_returns_appropriate_values(): void
    {
        // Highest severity for fraud/sanctions
        $this->assertSame(5, VendorHoldReason::FRAUD_SUSPECTED->severityLevel());
        $this->assertSame(5, VendorHoldReason::SANCTIONS_LIST->severityLevel());

        // Lower severity for document issues
        $this->assertSame(1, VendorHoldReason::CERTIFICATE_EXPIRED->severityLevel());
    }

    #[Test]
    public function setting_key_returns_valid_format(): void
    {
        $reason = VendorHoldReason::COMPLIANCE_PENDING;

        $key = $reason->settingKey();

        $this->assertStringStartsWith('procurement.vendor_hold.', $key);
        $this->assertSame('procurement.vendor_hold.compliance_pending', $key);
    }
}
