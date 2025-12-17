<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Tests\Unit\Enums;

use Nexus\KycVerification\Enums\DocumentType;
use Nexus\KycVerification\Enums\DueDiligenceLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DueDiligenceLevel::class)]
final class DueDiligenceLevelTest extends TestCase
{
    #[Test]
    public function it_has_all_expected_cases(): void
    {
        $expectedCases = ['SIMPLIFIED', 'STANDARD', 'ENHANCED'];

        $actualCases = array_map(fn(DueDiligenceLevel $case) => $case->name, DueDiligenceLevel::cases());

        foreach ($expectedCases as $expected) {
            $this->assertContains($expected, $actualCases);
        }
    }

    #[Test]
    public function it_returns_required_document_types(): void
    {
        $simplifiedDocs = DueDiligenceLevel::SIMPLIFIED->requiredDocumentTypes();
        $this->assertContains(DocumentType::NATIONAL_ID, $simplifiedDocs);

        $standardDocs = DueDiligenceLevel::STANDARD->requiredDocumentTypes();
        $this->assertContains(DocumentType::PASSPORT, $standardDocs);
        $this->assertContains(DocumentType::UTILITY_BILL, $standardDocs);

        $enhancedDocs = DueDiligenceLevel::ENHANCED->requiredDocumentTypes();
        $this->assertContains(DocumentType::PASSPORT, $enhancedDocs);
        $this->assertContains(DocumentType::UTILITY_BILL, $enhancedDocs);
        $this->assertContains(DocumentType::BANK_STATEMENT, $enhancedDocs);
        $this->assertContains(DocumentType::SELFIE, $enhancedDocs);
    }

    #[Test]
    public function it_returns_required_corporate_document_types(): void
    {
        $simplifiedDocs = DueDiligenceLevel::SIMPLIFIED->requiredCorporateDocumentTypes();
        $this->assertContains(DocumentType::BUSINESS_LICENSE, $simplifiedDocs);

        $standardDocs = DueDiligenceLevel::STANDARD->requiredCorporateDocumentTypes();
        $this->assertContains(DocumentType::CERTIFICATE_OF_INCORPORATION, $standardDocs);
        $this->assertContains(DocumentType::BUSINESS_LICENSE, $standardDocs);

        $enhancedDocs = DueDiligenceLevel::ENHANCED->requiredCorporateDocumentTypes();
        $this->assertContains(DocumentType::CERTIFICATE_OF_INCORPORATION, $enhancedDocs);
        $this->assertContains(DocumentType::ARTICLES_OF_ASSOCIATION, $enhancedDocs);
        $this->assertContains(DocumentType::UBO_DECLARATION, $enhancedDocs);
    }

    #[Test]
    #[DataProvider('uboTrackingProvider')]
    public function it_identifies_ubo_tracking_requirements(DueDiligenceLevel $level, bool $expected): void
    {
        $this->assertSame($expected, $level->requiresUboTracking());
    }

    public static function uboTrackingProvider(): array
    {
        return [
            'simplified does not require UBO tracking' => [DueDiligenceLevel::SIMPLIFIED, false],
            'standard requires UBO tracking' => [DueDiligenceLevel::STANDARD, true],
            'enhanced requires UBO tracking' => [DueDiligenceLevel::ENHANCED, true],
        ];
    }

    #[Test]
    public function it_returns_minimum_verification_scores(): void
    {
        $this->assertSame(60, DueDiligenceLevel::SIMPLIFIED->minimumVerificationScore());
        $this->assertSame(75, DueDiligenceLevel::STANDARD->minimumVerificationScore());
        $this->assertSame(90, DueDiligenceLevel::ENHANCED->minimumVerificationScore());
    }

    #[Test]
    #[DataProvider('ongoingMonitoringProvider')]
    public function it_identifies_ongoing_monitoring_requirements(DueDiligenceLevel $level, bool $expected): void
    {
        $this->assertSame($expected, $level->requiresOngoingMonitoring());
    }

    public static function ongoingMonitoringProvider(): array
    {
        return [
            'simplified does not require ongoing monitoring' => [DueDiligenceLevel::SIMPLIFIED, false],
            'standard requires ongoing monitoring' => [DueDiligenceLevel::STANDARD, true],
            'enhanced requires ongoing monitoring' => [DueDiligenceLevel::ENHANCED, true],
        ];
    }

    #[Test]
    #[DataProvider('sourceOfFundsProvider')]
    public function it_identifies_source_of_funds_requirements(DueDiligenceLevel $level, bool $expected): void
    {
        $this->assertSame($expected, $level->requiresSourceOfFunds());
    }

    public static function sourceOfFundsProvider(): array
    {
        return [
            'simplified does not require source of funds' => [DueDiligenceLevel::SIMPLIFIED, false],
            'standard does not require source of funds' => [DueDiligenceLevel::STANDARD, false],
            'enhanced requires source of funds' => [DueDiligenceLevel::ENHANCED, true],
        ];
    }

    #[Test]
    #[DataProvider('sourceOfWealthProvider')]
    public function it_identifies_source_of_wealth_requirements(DueDiligenceLevel $level, bool $expected): void
    {
        $this->assertSame($expected, $level->requiresSourceOfWealth());
    }

    public static function sourceOfWealthProvider(): array
    {
        return [
            'simplified does not require source of wealth' => [DueDiligenceLevel::SIMPLIFIED, false],
            'standard does not require source of wealth' => [DueDiligenceLevel::STANDARD, false],
            'enhanced requires source of wealth' => [DueDiligenceLevel::ENHANCED, true],
        ];
    }

    #[Test]
    public function it_returns_human_readable_labels(): void
    {
        $this->assertSame('Simplified Due Diligence (SDD)', DueDiligenceLevel::SIMPLIFIED->label());
        $this->assertSame('Standard Due Diligence (CDD)', DueDiligenceLevel::STANDARD->label());
        $this->assertSame('Enhanced Due Diligence (EDD)', DueDiligenceLevel::ENHANCED->label());
    }

    #[Test]
    public function it_is_backed_by_string_values(): void
    {
        $this->assertSame('simplified', DueDiligenceLevel::SIMPLIFIED->value);
        $this->assertSame('standard', DueDiligenceLevel::STANDARD->value);
        $this->assertSame('enhanced', DueDiligenceLevel::ENHANCED->value);
    }
}
