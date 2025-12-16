<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Tests\Unit\Enums;

use Nexus\AmlCompliance\Enums\JurisdictionRisk;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JurisdictionRisk::class)]
final class JurisdictionRiskTest extends TestCase
{
    public function test_low_has_correct_value(): void
    {
        $this->assertSame('low', JurisdictionRisk::LOW->value);
    }

    public function test_medium_has_correct_value(): void
    {
        $this->assertSame('medium', JurisdictionRisk::MEDIUM->value);
    }

    public function test_high_has_correct_value(): void
    {
        $this->assertSame('high', JurisdictionRisk::HIGH->value);
    }

    public function test_very_high_has_correct_value(): void
    {
        $this->assertSame('very_high', JurisdictionRisk::VERY_HIGH->value);
    }

    public function test_all_cases_exist(): void
    {
        $cases = JurisdictionRisk::cases();
        $this->assertCount(4, $cases);
    }

    public function test_get_weight(): void
    {
        $this->assertSame(0.1, JurisdictionRisk::LOW->getWeight());
        $this->assertSame(0.4, JurisdictionRisk::MEDIUM->getWeight());
        $this->assertSame(0.7, JurisdictionRisk::HIGH->getWeight());
        $this->assertSame(1.0, JurisdictionRisk::VERY_HIGH->getWeight());
    }

    public function test_get_score_contribution(): void
    {
        $this->assertSame(10, JurisdictionRisk::LOW->getScoreContribution());
        $this->assertSame(40, JurisdictionRisk::MEDIUM->getScoreContribution());
        $this->assertSame(70, JurisdictionRisk::HIGH->getScoreContribution());
        $this->assertSame(100, JurisdictionRisk::VERY_HIGH->getScoreContribution());
    }

    public function test_get_fatf_grey_list_countries(): void
    {
        $countries = JurisdictionRisk::getFatfGreyListCountries();
        $this->assertIsArray($countries);
        $this->assertNotEmpty($countries);
    }

    public function test_get_fatf_black_list_countries(): void
    {
        $countries = JurisdictionRisk::getFatfBlackListCountries();
        $this->assertIsArray($countries);
    }

    public function test_get_eu_high_risk_countries(): void
    {
        $countries = JurisdictionRisk::getEuHighRiskCountries();
        $this->assertIsArray($countries);
    }

    public function test_get_low_risk_countries(): void
    {
        $countries = JurisdictionRisk::getLowRiskCountries();
        $this->assertIsArray($countries);
        $this->assertNotEmpty($countries);
    }

    public function test_from_country_code_high_risk(): void
    {
        // KP (North Korea) is typically on blacklist
        $risk = JurisdictionRisk::fromCountryCode('KP');
        $this->assertTrue($risk->isHigherThan(JurisdictionRisk::LOW));
    }

    public function test_from_country_code_low_risk(): void
    {
        // SG (Singapore) is typically low risk
        $risk = JurisdictionRisk::fromCountryCode('SG');
        $this->assertSame(JurisdictionRisk::LOW, $risk);
    }

    public function test_requires_edd(): void
    {
        $this->assertFalse(JurisdictionRisk::LOW->requiresEdd());
        $this->assertFalse(JurisdictionRisk::MEDIUM->requiresEdd());
        $this->assertTrue(JurisdictionRisk::HIGH->requiresEdd());
        $this->assertTrue(JurisdictionRisk::VERY_HIGH->requiresEdd());
    }

    public function test_is_prohibited(): void
    {
        $this->assertFalse(JurisdictionRisk::LOW->isProhibited());
        $this->assertFalse(JurisdictionRisk::MEDIUM->isProhibited());
        $this->assertFalse(JurisdictionRisk::HIGH->isProhibited());
    }

    public function test_get_description(): void
    {
        $this->assertIsString(JurisdictionRisk::LOW->getDescription());
        $this->assertNotEmpty(JurisdictionRisk::LOW->getDescription());
    }

    public function test_get_transaction_review_threshold(): void
    {
        $lowThreshold = JurisdictionRisk::LOW->getTransactionReviewThreshold();
        $highThreshold = JurisdictionRisk::HIGH->getTransactionReviewThreshold();

        $this->assertIsFloat($lowThreshold);
        $this->assertIsFloat($highThreshold);
        $this->assertLessThan($lowThreshold, $highThreshold);
    }

    public function test_is_higher_than(): void
    {
        $this->assertTrue(JurisdictionRisk::HIGH->isHigherThan(JurisdictionRisk::LOW));
        $this->assertFalse(JurisdictionRisk::LOW->isHigherThan(JurisdictionRisk::HIGH));
    }

    public function test_get_reporting_level(): void
    {
        $this->assertIsString(JurisdictionRisk::LOW->getReportingLevel());
    }

    public function test_ascending_returns_ordered_array(): void
    {
        $ascending = JurisdictionRisk::ascending();
        $this->assertIsArray($ascending);
        $this->assertCount(4, $ascending);
        $this->assertSame(JurisdictionRisk::LOW, $ascending[0]);
    }
}
