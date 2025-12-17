<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\Enums;

use Nexus\DataPrivacy\Enums\LawfulBasisType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(LawfulBasisType::class)]
final class LawfulBasisTypeTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $expectedCases = [
            'consent', 'contract', 'legal_obligation',
            'vital_interests', 'public_interest', 'legitimate_interests',
        ];

        $actualCases = array_map(fn(LawfulBasisType $t) => $t->value, LawfulBasisType::cases());
        
        foreach ($expectedCases as $expected) {
            $this->assertContains($expected, $actualCases);
        }
    }

    #[DataProvider('allTypesProvider')]
    public function testGetLabelReturnsString(LawfulBasisType $type): void
    {
        $label = $type->getLabel();
        $this->assertNotEmpty($label);
        $this->assertIsString($label);
    }

    public static function allTypesProvider(): array
    {
        return array_map(fn($t) => [$t], LawfulBasisType::cases());
    }

    public function testConsentRequiresExplicitConsent(): void
    {
        $this->assertTrue(LawfulBasisType::CONSENT->requiresExplicitConsent());
    }

    public function testContractDoesNotRequireExplicitConsent(): void
    {
        $this->assertFalse(LawfulBasisType::CONTRACT->requiresExplicitConsent());
    }

    public function testLegalObligationDoesNotRequireExplicitConsent(): void
    {
        $this->assertFalse(LawfulBasisType::LEGAL_OBLIGATION->requiresExplicitConsent());
    }
}
