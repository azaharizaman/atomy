<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Tests\Unit\Enums;

use Nexus\KycVerification\Enums\PartyType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PartyType::class)]
final class PartyTypeTest extends TestCase
{
    #[Test]
    public function it_has_all_expected_cases(): void
    {
        $expectedCases = [
            'INDIVIDUAL', 'CORPORATE', 'SOLE_PROPRIETORSHIP', 'PARTNERSHIP',
            'TRUST', 'FOUNDATION', 'GOVERNMENT', 'NON_PROFIT',
        ];

        $actualCases = array_map(fn(PartyType $case) => $case->name, PartyType::cases());

        foreach ($expectedCases as $expected) {
            $this->assertContains($expected, $actualCases);
        }
    }

    #[Test]
    #[DataProvider('uboTrackingProvider')]
    public function it_identifies_ubo_tracking_requirements(PartyType $type, bool $expected): void
    {
        $this->assertSame($expected, $type->requiresUboTracking());
    }

    public static function uboTrackingProvider(): array
    {
        return [
            'individual does not require UBO tracking' => [PartyType::INDIVIDUAL, false],
            'corporate requires UBO tracking' => [PartyType::CORPORATE, true],
            'partnership requires UBO tracking' => [PartyType::PARTNERSHIP, true],
            'trust requires UBO tracking' => [PartyType::TRUST, true],
            'foundation requires UBO tracking' => [PartyType::FOUNDATION, true],
            'sole proprietorship does not require UBO tracking' => [PartyType::SOLE_PROPRIETORSHIP, false],
            'government does not require UBO tracking' => [PartyType::GOVERNMENT, false],
            'non-profit does not require UBO tracking' => [PartyType::NON_PROFIT, false],
        ];
    }

    #[Test]
    #[DataProvider('legalEntityProvider')]
    public function it_identifies_legal_entities(PartyType $type, bool $expected): void
    {
        $this->assertSame($expected, $type->isLegalEntity());
    }

    public static function legalEntityProvider(): array
    {
        return [
            'individual is not legal entity' => [PartyType::INDIVIDUAL, false],
            'corporate is legal entity' => [PartyType::CORPORATE, true],
            'partnership is legal entity' => [PartyType::PARTNERSHIP, true],
            'sole proprietorship is legal entity' => [PartyType::SOLE_PROPRIETORSHIP, true],
            'trust is legal entity' => [PartyType::TRUST, true],
            'foundation is legal entity' => [PartyType::FOUNDATION, true],
            'government is legal entity' => [PartyType::GOVERNMENT, true],
            'non-profit is legal entity' => [PartyType::NON_PROFIT, true],
        ];
    }

    #[Test]
    public function it_returns_required_document_categories(): void
    {
        $individualCats = PartyType::INDIVIDUAL->requiredDocumentCategories();
        $this->assertContains('identity', $individualCats);
        $this->assertContains('address', $individualCats);

        $corporateCats = PartyType::CORPORATE->requiredDocumentCategories();
        $this->assertContains('corporate', $corporateCats);
        $this->assertContains('identity', $corporateCats);
        $this->assertContains('ubo', $corporateCats);

        $trustCats = PartyType::TRUST->requiredDocumentCategories();
        $this->assertContains('trust', $trustCats);
        $this->assertContains('ubo', $trustCats);

        $govCats = PartyType::GOVERNMENT->requiredDocumentCategories();
        $this->assertContains('authorization', $govCats);
    }

    #[Test]
    public function it_returns_human_readable_labels(): void
    {
        $this->assertSame('Individual', PartyType::INDIVIDUAL->label());
        $this->assertSame('Corporation', PartyType::CORPORATE->label());
        $this->assertSame('Sole Proprietorship', PartyType::SOLE_PROPRIETORSHIP->label());
        $this->assertSame('Partnership', PartyType::PARTNERSHIP->label());
        $this->assertSame('Trust', PartyType::TRUST->label());
        $this->assertSame('Foundation', PartyType::FOUNDATION->label());
        $this->assertSame('Government Entity', PartyType::GOVERNMENT->label());
        $this->assertSame('Non-Profit Organization', PartyType::NON_PROFIT->label());
    }

    #[Test]
    public function it_is_backed_by_string_values(): void
    {
        $this->assertSame('individual', PartyType::INDIVIDUAL->value);
        $this->assertSame('corporate', PartyType::CORPORATE->value);
        $this->assertSame('sole_proprietorship', PartyType::SOLE_PROPRIETORSHIP->value);
        $this->assertSame('partnership', PartyType::PARTNERSHIP->value);
        $this->assertSame('trust', PartyType::TRUST->value);
    }
}
