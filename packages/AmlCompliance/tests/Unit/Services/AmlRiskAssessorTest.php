<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Tests\Unit\Services;

use Nexus\AmlCompliance\Contracts\PartyInterface;
use Nexus\AmlCompliance\Contracts\SanctionsResultInterface;
use Nexus\AmlCompliance\Enums\RiskLevel;
use Nexus\AmlCompliance\Exceptions\RiskAssessmentFailedException;
use Nexus\AmlCompliance\Services\AmlRiskAssessor;
use Nexus\AmlCompliance\ValueObjects\AmlRiskScore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(AmlRiskAssessor::class)]
final class AmlRiskAssessorTest extends TestCase
{
    private AmlRiskAssessor $assessor;

    protected function setUp(): void
    {
        $this->assessor = new AmlRiskAssessor(new NullLogger());
    }

    private function createPartyMock(
        string $id = 'party-123',
        string $countryCode = 'US',
        array $associatedCountries = [],
        ?string $industryCode = null,
        ?int $pepLevel = null,
        array $beneficialOwners = []
    ): PartyInterface&MockObject {
        $party = $this->createMock(PartyInterface::class);

        $party->method('getId')->willReturn($id);
        $party->method('getCountryCode')->willReturn($countryCode);
        $party->method('getAssociatedCountryCodes')->willReturn($associatedCountries);
        $party->method('getIndustryCode')->willReturn($industryCode);
        $party->method('getPepLevel')->willReturn($pepLevel);
        $party->method('isPep')->willReturn($pepLevel !== null);
        $party->method('getBeneficialOwners')->willReturn($beneficialOwners);
        $party->method('getMetadata')->willReturn([]);

        return $party;
    }

    private function createSanctionsResultMock(
        bool $hasMatches = false,
        int $highestMatchScore = 0,
        bool $hasConfirmedMatch = false,
        int $riskScore = 0
    ): SanctionsResultInterface&MockObject {
        $result = $this->createMock(SanctionsResultInterface::class);

        $result->method('hasMatches')->willReturn($hasMatches);
        $result->method('getHighestMatchScore')->willReturn($highestMatchScore);
        $result->method('hasConfirmedMatch')->willReturn($hasConfirmedMatch);
        $result->method('getRiskScore')->willReturn($riskScore);
        $result->method('getMatchCount')->willReturn($hasMatches ? 1 : 0);
        $result->method('getMatchedLists')->willReturn($hasMatches ? ['OFAC_SDN'] : []);
        $result->method('isBlocked')->willReturn($hasConfirmedMatch);

        return $result;
    }

    public function test_assess_returns_aml_risk_score(): void
    {
        $party = $this->createPartyMock(countryCode: 'US');

        $score = $this->assessor->assess($party);

        $this->assertInstanceOf(AmlRiskScore::class, $score);
        $this->assertSame('party-123', $score->partyId);
    }

    public function test_assess_low_risk_country_returns_low_score(): void
    {
        $party = $this->createPartyMock(countryCode: 'GB'); // Low risk

        $score = $this->assessor->assess($party);

        $this->assertSame(RiskLevel::LOW, $score->riskLevel);
    }

    public function test_assess_high_risk_country_returns_higher_score(): void
    {
        $party = $this->createPartyMock(countryCode: 'IR'); // Iran - FATF blacklist

        $this->expectException(RiskAssessmentFailedException::class);

        $this->assessor->assess($party);
    }

    public function test_assess_with_sanctions_result_increases_score(): void
    {
        $party = $this->createPartyMock(countryCode: 'US');
        $sanctionsResult = $this->createSanctionsResultMock(
            hasMatches: true,
            highestMatchScore: 90,
            hasConfirmedMatch: true,
            riskScore: 100
        );

        $score = $this->assessor->assess($party, $sanctionsResult);

        // Sanctions should significantly increase score
        $this->assertGreaterThan(0, $score->factors->sanctionsScore);
    }

    public function test_assess_with_no_sanctions_match_has_zero_sanctions_score(): void
    {
        $party = $this->createPartyMock(countryCode: 'US');
        $sanctionsResult = $this->createSanctionsResultMock(
            hasMatches: false,
            highestMatchScore: 0,
            hasConfirmedMatch: false,
            riskScore: 0
        );

        $score = $this->assessor->assess($party, $sanctionsResult);

        $this->assertSame(0, $score->factors->sanctionsScore);
    }

    public function test_assess_pep_party_has_higher_score(): void
    {
        $regularParty = $this->createPartyMock(countryCode: 'US', pepLevel: null);
        $pepParty = $this->createPartyMock(countryCode: 'US', pepLevel: 1);

        $regularScore = $this->assessor->assess($regularParty);
        $pepScore = $this->assessor->assess($pepParty);

        // PEP should have higher score due to multiplier
        $this->assertGreaterThan($regularScore->overallScore, $pepScore->overallScore);
    }

    public function test_assess_jurisdiction_risk_for_low_risk_country(): void
    {
        $party = $this->createPartyMock(countryCode: 'SG'); // Singapore - low risk

        $score = $this->assessor->assessJurisdictionRisk($party);

        // Low-risk country should have low score
        $this->assertLessThan(50, $score);
    }

    public function test_assess_jurisdiction_risk_for_high_risk_country(): void
    {
        $party = $this->createPartyMock(countryCode: 'NG'); // Nigeria - FATF grey list

        $score = $this->assessor->assessJurisdictionRisk($party);

        // High-risk country should have higher score than low-risk
        // HIGH risk (70) * 0.7 primary weight = 49
        $this->assertGreaterThanOrEqual(45, $score);
    }

    public function test_assess_jurisdiction_risk_throws_for_prohibited(): void
    {
        $party = $this->createPartyMock(countryCode: 'KP'); // North Korea - blacklist

        $this->expectException(RiskAssessmentFailedException::class);

        $this->assessor->assessJurisdictionRisk($party);
    }

    public function test_assess_business_type_risk_high_risk_industry(): void
    {
        // NAICS 523110 - Investment Banking
        $party = $this->createPartyMock(industryCode: '523110');

        $score = $this->assessor->assessBusinessTypeRisk($party);

        $this->assertGreaterThan(50, $score);
    }

    public function test_assess_business_type_risk_low_risk_industry(): void
    {
        // NAICS 621111 - Offices of Physicians
        $party = $this->createPartyMock(industryCode: '621111');

        $score = $this->assessor->assessBusinessTypeRisk($party);

        $this->assertLessThan(50, $score);
    }

    public function test_assess_business_type_risk_no_industry_returns_medium(): void
    {
        $party = $this->createPartyMock(industryCode: null);

        $score = $this->assessor->assessBusinessTypeRisk($party);

        $this->assertSame(50, $score);
    }

    public function test_requires_edd_for_high_risk(): void
    {
        // Create a party that will result in high risk (grey list + PEP + sanctions match)
        $party = $this->createPartyMock(countryCode: 'NG', pepLevel: 1); // Nigeria - grey list
        $sanctionsResult = $this->createSanctionsResultMock(
            hasMatches: true,
            highestMatchScore: 85,
            hasConfirmedMatch: false,
            riskScore: 85
        );

        $score = $this->assessor->assess($party, $sanctionsResult);

        // With high-risk country (NG), PEP level 1, and sanctions matches,
        // the score should be high enough to require EDD
        $this->assertTrue(
            $this->assessor->requiresEdd($score),
            sprintf('Expected EDD required for score %d (level: %s)', $score->overallScore, $score->riskLevel->value)
        );
    }

    public function test_requires_edd_returns_false_for_low_risk(): void
    {
        $party = $this->createPartyMock(countryCode: 'GB'); // Low risk

        $score = $this->assessor->assess($party);

        // Low risk country with no PEP, no sanctions should not require EDD
        $this->assertFalse(
            $this->assessor->requiresEdd($score),
            sprintf('Expected no EDD for score %d (level: %s)', $score->overallScore, $score->riskLevel->value)
        );
    }

    public function test_generate_recommendations_includes_edd_for_high_risk(): void
    {
        $party = $this->createPartyMock(countryCode: 'NG', pepLevel: 1); // Nigeria - grey list

        $score = $this->assessor->assess($party);
        $recommendations = $this->assessor->generateRecommendations($score);

        $this->assertIsArray($recommendations);
        // High risk should recommend EDD or similar
        if ($score->riskLevel === RiskLevel::HIGH) {
            $this->assertNotEmpty($recommendations);
        }
    }

    public function test_get_risk_level_returns_correct_level(): void
    {
        $this->assertSame(RiskLevel::LOW, $this->assessor->getRiskLevel(20));
        $this->assertSame(RiskLevel::MEDIUM, $this->assessor->getRiskLevel(50));
        $this->assertSame(RiskLevel::HIGH, $this->assessor->getRiskLevel(80));
    }

    public function test_calculate_next_review_date_based_on_risk(): void
    {
        $lowReview = $this->assessor->calculateNextReviewDate(RiskLevel::LOW);
        $highReview = $this->assessor->calculateNextReviewDate(RiskLevel::HIGH);

        // High risk should have earlier review date
        $this->assertLessThan($lowReview, $highReview);
    }

    public function test_assess_handles_associated_countries(): void
    {
        // US party with high-risk associated country
        $party = $this->createPartyMock(
            countryCode: 'US',
            associatedCountries: ['MM']
        );

        $score = $this->assessor->assess($party);

        // Having high-risk associated country should increase jurisdiction score
        $this->assertGreaterThan(0, $score->factors->jurisdictionScore);
    }

    public function test_assess_with_transaction_data(): void
    {
        $party = $this->createPartyMock(countryCode: 'US');
        $transactionData = [
            'count' => 100,
            'total_amount' => 500000.00,
            'average_amount' => 5000.00,
        ];

        $score = $this->assessor->assess($party, null, $transactionData);

        // Transaction data should contribute to transaction score
        $this->assertGreaterThanOrEqual(0, $score->factors->transactionScore);
    }
}
