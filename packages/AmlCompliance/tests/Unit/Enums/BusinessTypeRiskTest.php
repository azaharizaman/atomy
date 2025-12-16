<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Tests\Unit\Enums;

use Nexus\AmlCompliance\Enums\BusinessTypeRisk;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BusinessTypeRisk::class)]
final class BusinessTypeRiskTest extends TestCase
{
    public function test_low_has_correct_value(): void
    {
        $this->assertSame('low', BusinessTypeRisk::LOW->value);
    }

    public function test_medium_has_correct_value(): void
    {
        $this->assertSame('medium', BusinessTypeRisk::MEDIUM->value);
    }

    public function test_high_has_correct_value(): void
    {
        $this->assertSame('high', BusinessTypeRisk::HIGH->value);
    }

    public function test_all_cases_exist(): void
    {
        $cases = BusinessTypeRisk::cases();
        $this->assertCount(3, $cases);
    }

    public function test_get_weight(): void
    {
        $this->assertSame(0.1, BusinessTypeRisk::LOW->getWeight());
        $this->assertSame(0.4, BusinessTypeRisk::MEDIUM->getWeight());
        $this->assertSame(0.8, BusinessTypeRisk::HIGH->getWeight());
    }

    public function test_get_score_contribution(): void
    {
        $this->assertSame(10, BusinessTypeRisk::LOW->getScoreContribution());
        $this->assertSame(50, BusinessTypeRisk::MEDIUM->getScoreContribution());
        $this->assertSame(100, BusinessTypeRisk::HIGH->getScoreContribution());
    }

    public function test_get_high_risk_industries(): void
    {
        $industries = BusinessTypeRisk::getHighRiskIndustries();
        $this->assertIsArray($industries);
        $this->assertNotEmpty($industries);
        $this->assertArrayHasKey('MSB', $industries);
        $this->assertArrayHasKey('CRYPTO', $industries);
        $this->assertArrayHasKey('GAMBLING', $industries);
    }

    public function test_get_medium_risk_industries(): void
    {
        $industries = BusinessTypeRisk::getMediumRiskIndustries();
        $this->assertIsArray($industries);
        $this->assertNotEmpty($industries);
        $this->assertArrayHasKey('REAL_ESTATE', $industries);
    }

    public function test_get_low_risk_industries(): void
    {
        $industries = BusinessTypeRisk::getLowRiskIndustries();
        $this->assertIsArray($industries);
        $this->assertNotEmpty($industries);
        $this->assertArrayHasKey('RETAIL', $industries);
        $this->assertArrayHasKey('TECHNOLOGY', $industries);
    }

    public function test_from_industry_code_high_risk(): void
    {
        $this->assertSame(BusinessTypeRisk::HIGH, BusinessTypeRisk::fromIndustryCode('MSB'));
        $this->assertSame(BusinessTypeRisk::HIGH, BusinessTypeRisk::fromIndustryCode('CRYPTO'));
        $this->assertSame(BusinessTypeRisk::HIGH, BusinessTypeRisk::fromIndustryCode('GAMBLING'));
    }

    public function test_from_industry_code_medium_risk(): void
    {
        $this->assertSame(BusinessTypeRisk::MEDIUM, BusinessTypeRisk::fromIndustryCode('REAL_ESTATE'));
        $this->assertSame(BusinessTypeRisk::MEDIUM, BusinessTypeRisk::fromIndustryCode('CONSTRUCTION'));
    }

    public function test_from_industry_code_low_risk(): void
    {
        $this->assertSame(BusinessTypeRisk::LOW, BusinessTypeRisk::fromIndustryCode('RETAIL'));
        $this->assertSame(BusinessTypeRisk::LOW, BusinessTypeRisk::fromIndustryCode('TECHNOLOGY'));
    }

    public function test_from_industry_code_unknown_defaults_to_medium(): void
    {
        $this->assertSame(BusinessTypeRisk::MEDIUM, BusinessTypeRisk::fromIndustryCode('UNKNOWN_CODE'));
    }

    public function test_from_industry_code_is_case_insensitive(): void
    {
        $this->assertSame(BusinessTypeRisk::HIGH, BusinessTypeRisk::fromIndustryCode('msb'));
        $this->assertSame(BusinessTypeRisk::HIGH, BusinessTypeRisk::fromIndustryCode('Msb'));
    }

    public function test_from_naics_code_gambling(): void
    {
        $risk = BusinessTypeRisk::fromNaicsCode('713200');
        $this->assertSame(BusinessTypeRisk::HIGH, $risk);
    }

    public function test_from_naics_code_construction(): void
    {
        $risk = BusinessTypeRisk::fromNaicsCode('236115');
        $this->assertSame(BusinessTypeRisk::MEDIUM, $risk);
    }

    public function test_requires_edd(): void
    {
        $this->assertFalse(BusinessTypeRisk::LOW->requiresEdd());
        $this->assertFalse(BusinessTypeRisk::MEDIUM->requiresEdd());
        $this->assertTrue(BusinessTypeRisk::HIGH->requiresEdd());
    }

    public function test_is_cash_intensive(): void
    {
        // HIGH risk is typically cash-intensive
        $this->assertIsBool(BusinessTypeRisk::HIGH->isCashIntensive());
    }

    public function test_get_description(): void
    {
        $this->assertIsString(BusinessTypeRisk::LOW->getDescription());
        $this->assertNotEmpty(BusinessTypeRisk::LOW->getDescription());
        $this->assertIsString(BusinessTypeRisk::MEDIUM->getDescription());
        $this->assertIsString(BusinessTypeRisk::HIGH->getDescription());
    }

    public function test_get_monitoring_intensity(): void
    {
        $low = BusinessTypeRisk::LOW->getMonitoringIntensity();
        $high = BusinessTypeRisk::HIGH->getMonitoringIntensity();

        $this->assertIsString($low);
        $this->assertIsString($high);
    }

    public function test_ascending_returns_ordered_array(): void
    {
        $ascending = BusinessTypeRisk::ascending();
        $this->assertIsArray($ascending);
        $this->assertCount(3, $ascending);
        $this->assertSame(BusinessTypeRisk::LOW, $ascending[0]);
        $this->assertSame(BusinessTypeRisk::MEDIUM, $ascending[1]);
        $this->assertSame(BusinessTypeRisk::HIGH, $ascending[2]);
    }

    public function test_is_higher_than(): void
    {
        $this->assertTrue(BusinessTypeRisk::HIGH->isHigherThan(BusinessTypeRisk::LOW));
        $this->assertTrue(BusinessTypeRisk::HIGH->isHigherThan(BusinessTypeRisk::MEDIUM));
        $this->assertFalse(BusinessTypeRisk::LOW->isHigherThan(BusinessTypeRisk::HIGH));
        $this->assertFalse(BusinessTypeRisk::MEDIUM->isHigherThan(BusinessTypeRisk::MEDIUM));
    }
}
