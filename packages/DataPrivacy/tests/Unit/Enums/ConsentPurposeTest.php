<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\Enums;

use Nexus\DataPrivacy\Enums\ConsentPurpose;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConsentPurpose::class)]
final class ConsentPurposeTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $expectedCases = [
            'service_delivery', 'marketing_email', 'marketing_sms',
            'marketing_phone', 'third_party_marketing', 'analytics',
            'personalization', 'profiling', 'location_tracking',
            'cookies', 'research', 'sensitive_data',
            'cross_border_transfer', 'newsletter', 'children_data',
        ];

        $actualCases = array_map(fn(ConsentPurpose $p) => $p->value, ConsentPurpose::cases());
        
        foreach ($expectedCases as $expected) {
            $this->assertContains($expected, $actualCases);
        }
    }

    #[DataProvider('allPurposesProvider')]
    public function testGetLabelReturnsString(ConsentPurpose $purpose): void
    {
        $label = $purpose->getLabel();
        $this->assertNotEmpty($label);
        $this->assertIsString($label);
    }

    public static function allPurposesProvider(): array
    {
        return array_map(fn($p) => [$p], ConsentPurpose::cases());
    }

    public function testMarketingPurposesRequireOptIn(): void
    {
        $marketingPurposes = [
            ConsentPurpose::MARKETING_EMAIL,
            ConsentPurpose::MARKETING_SMS,
            ConsentPurpose::MARKETING_PHONE,
            ConsentPurpose::THIRD_PARTY_MARKETING,
        ];

        foreach ($marketingPurposes as $purpose) {
            $this->assertTrue($purpose->requiresOptIn(), "{$purpose->value} should require opt-in");
        }
    }

    public function testServiceDeliveryDoesNotRequireOptIn(): void
    {
        $this->assertFalse(ConsentPurpose::SERVICE_DELIVERY->requiresOptIn());
    }

    public function testSensitiveDataRequiresExplicitConsent(): void
    {
        $this->assertTrue(ConsentPurpose::SENSITIVE_DATA->requiresExplicitConsent());
    }

    public function testChildrenDataRequiresParentalConsent(): void
    {
        $this->assertTrue(ConsentPurpose::CHILDREN_DATA->requiresParentalConsent());
    }
}
