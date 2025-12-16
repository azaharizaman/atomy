<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Tests\Unit\ValueObjects;

use Nexus\AmlCompliance\Enums\RiskLevel;
use Nexus\AmlCompliance\ValueObjects\AmlRiskScore;
use Nexus\AmlCompliance\ValueObjects\RiskFactors;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AmlRiskScore::class)]
final class AmlRiskScoreTest extends TestCase
{
    private function createRiskFactors(int $score = 50): RiskFactors
    {
        return new RiskFactors($score, $score, $score, $score);
    }

    public function test_constructor_sets_all_properties(): void
    {
        $factors = $this->createRiskFactors(50);
        $assessedAt = new \DateTimeImmutable();

        $score = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $factors,
            assessedAt: $assessedAt,
        );

        $this->assertSame('party-123', $score->partyId);
        $this->assertSame(50, $score->overallScore);
        $this->assertSame(RiskLevel::MEDIUM, $score->riskLevel);
        $this->assertSame($factors, $score->factors);
        $this->assertSame($assessedAt, $score->assessedAt);
    }

    public function test_constructor_throws_for_negative_score(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AmlRiskScore(
            partyId: 'party-123',
            overallScore: -1,
            riskLevel: RiskLevel::LOW,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable(),
        );
    }

    public function test_constructor_throws_for_score_above_100(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 101,
            riskLevel: RiskLevel::HIGH,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable(),
        );
    }

    public function test_from_factors_creates_score(): void
    {
        $factors = new RiskFactors(20, 20, 20, 20);

        $score = AmlRiskScore::fromFactors('party-123', $factors);

        $this->assertSame('party-123', $score->partyId);
        $this->assertSame($factors, $score->factors);
        $this->assertSame(RiskLevel::LOW, $score->riskLevel);
        $this->assertInstanceOf(\DateTimeImmutable::class, $score->assessedAt);
    }

    public function test_from_factors_sets_assessed_by(): void
    {
        $factors = $this->createRiskFactors();

        $score = AmlRiskScore::fromFactors('party-123', $factors, 'user-456');

        $this->assertSame('user-456', $score->assessedBy);
    }

    public function test_from_factors_sets_next_review_date(): void
    {
        $factors = $this->createRiskFactors();

        $score = AmlRiskScore::fromFactors('party-123', $factors);

        $this->assertInstanceOf(\DateTimeImmutable::class, $score->nextReviewDate);
    }

    public function test_from_array_creates_instance(): void
    {
        $data = [
            'party_id' => 'party-123',
            'overall_score' => 60,
            'risk_level' => 'medium',
            'factors' => [
                'jurisdiction_score' => 50,
                'business_type_score' => 50,
                'sanctions_score' => 50,
                'transaction_score' => 50,
            ],
            'assessed_at' => '2024-01-15T10:00:00+00:00',
        ];

        $score = AmlRiskScore::fromArray($data);

        $this->assertSame('party-123', $score->partyId);
        $this->assertSame(60, $score->overallScore);
        $this->assertSame(RiskLevel::MEDIUM, $score->riskLevel);
    }

    public function test_requires_edd(): void
    {
        // HIGH risk requires EDD
        $highScore = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 80,
            riskLevel: RiskLevel::HIGH,
            factors: $this->createRiskFactors(80),
            assessedAt: new \DateTimeImmutable(),
        );
        $this->assertTrue($highScore->requiresEdd());

        // LOW risk does not require EDD
        $lowScore = new AmlRiskScore(
            partyId: 'party-456',
            overallScore: 20,
            riskLevel: RiskLevel::LOW,
            factors: $this->createRiskFactors(20),
            assessedAt: new \DateTimeImmutable(),
        );
        $this->assertFalse($lowScore->requiresEdd());
    }

    public function test_requires_enhanced_monitoring(): void
    {
        $highScore = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 80,
            riskLevel: RiskLevel::HIGH,
            factors: $this->createRiskFactors(80),
            assessedAt: new \DateTimeImmutable(),
        );

        $this->assertTrue($highScore->requiresEnhancedMonitoring());
    }

    public function test_is_review_due_soon(): void
    {
        $upcomingReview = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable('-11 months'),
            nextReviewDate: new \DateTimeImmutable('+15 days'),
        );

        $this->assertTrue($upcomingReview->isReviewDueSoon(30));
        $this->assertFalse($upcomingReview->isReviewDueSoon(10));
    }

    public function test_is_review_overdue(): void
    {
        $pastReview = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable('-1 year'),
            nextReviewDate: new \DateTimeImmutable('-1 month'),
        );

        $this->assertTrue($pastReview->isReviewOverdue());
    }

    public function test_to_array_returns_structured_data(): void
    {
        $factors = $this->createRiskFactors();

        $score = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $factors,
            assessedAt: new \DateTimeImmutable(),
        );

        $array = $score->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('party_id', $array);
        $this->assertArrayHasKey('overall_score', $array);
        $this->assertArrayHasKey('risk_level', $array);
        $this->assertArrayHasKey('factors', $array);
        $this->assertArrayHasKey('assessed_at', $array);
    }

    public function test_get_days_until_review_returns_positive_for_future_date(): void
    {
        $score = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable(),
            nextReviewDate: new \DateTimeImmutable('+30 days'),
        );

        $daysUntilReview = $score->getDaysUntilReview();

        $this->assertNotNull($daysUntilReview);
        $this->assertGreaterThanOrEqual(29, $daysUntilReview);
        $this->assertLessThanOrEqual(31, $daysUntilReview);
    }

    public function test_get_days_until_review_returns_negative_for_past_date(): void
    {
        $score = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable('-1 year'),
            nextReviewDate: new \DateTimeImmutable('-10 days'),
        );

        $daysUntilReview = $score->getDaysUntilReview();

        $this->assertNotNull($daysUntilReview);
        $this->assertLessThan(0, $daysUntilReview);
    }

    public function test_get_days_until_review_returns_null_when_no_review_date(): void
    {
        $score = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable(),
            nextReviewDate: null,
        );

        $this->assertNull($score->getDaysUntilReview());
    }

    public function test_is_review_overdue_false_when_no_review_date(): void
    {
        $score = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable(),
            nextReviewDate: null,
        );

        $this->assertFalse($score->isReviewOverdue());
    }

    public function test_is_review_overdue_false_for_future_date(): void
    {
        $score = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable(),
            nextReviewDate: new \DateTimeImmutable('+30 days'),
        );

        $this->assertFalse($score->isReviewOverdue());
    }

    public function test_is_review_due_soon_false_when_no_review_date(): void
    {
        $score = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable(),
            nextReviewDate: null,
        );

        $this->assertFalse($score->isReviewDueSoon());
    }

    public function test_has_increased_from_returns_true_when_score_higher(): void
    {
        $previous = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 40,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable('-6 months'),
        );

        $current = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 70,
            riskLevel: RiskLevel::HIGH,
            factors: new RiskFactors(80, 50, 0, 30),
            assessedAt: new \DateTimeImmutable(),
        );

        $this->assertTrue($current->hasIncreasedFrom($previous));
    }

    public function test_has_increased_from_returns_false_when_score_lower(): void
    {
        $previous = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 70,
            riskLevel: RiskLevel::HIGH,
            factors: new RiskFactors(80, 50, 0, 30),
            assessedAt: new \DateTimeImmutable('-6 months'),
        );

        $current = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 40,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable(),
        );

        $this->assertFalse($current->hasIncreasedFrom($previous));
    }

    public function test_has_increased_from_returns_false_when_same_score(): void
    {
        $previous = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable('-6 months'),
        );

        $current = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable(),
        );

        $this->assertFalse($current->hasIncreasedFrom($previous));
    }

    public function test_get_score_change_returns_positive_for_increase(): void
    {
        $previous = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 40,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable('-6 months'),
        );

        $current = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 65,
            riskLevel: RiskLevel::HIGH,
            factors: new RiskFactors(80, 50, 0, 30),
            assessedAt: new \DateTimeImmutable(),
        );

        $this->assertSame(25, $current->getScoreChange($previous));
    }

    public function test_get_score_change_returns_negative_for_decrease(): void
    {
        $previous = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 70,
            riskLevel: RiskLevel::HIGH,
            factors: new RiskFactors(80, 50, 0, 30),
            assessedAt: new \DateTimeImmutable('-6 months'),
        );

        $current = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 40,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable(),
        );

        $this->assertSame(-30, $current->getScoreChange($previous));
    }

    public function test_get_score_change_returns_zero_for_no_change(): void
    {
        $previous = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable('-6 months'),
        );

        $current = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable(),
        );

        $this->assertSame(0, $current->getScoreChange($previous));
    }

    public function test_has_escalated_from_returns_true_when_risk_level_higher(): void
    {
        $previous = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 40,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable('-6 months'),
        );

        $current = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 85,
            riskLevel: RiskLevel::HIGH,
            factors: new RiskFactors(95, 50, 0, 30),
            assessedAt: new \DateTimeImmutable(),
        );

        $this->assertTrue($current->hasEscalatedFrom($previous));
    }

    public function test_has_escalated_from_returns_false_when_risk_level_same(): void
    {
        $previous = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable('-6 months'),
        );

        $current = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 55,
            riskLevel: RiskLevel::MEDIUM,
            factors: new RiskFactors(60, 50, 0, 30),
            assessedAt: new \DateTimeImmutable(),
        );

        $this->assertFalse($current->hasEscalatedFrom($previous));
    }

    public function test_has_escalated_from_returns_false_when_risk_level_lower(): void
    {
        $previous = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 80,
            riskLevel: RiskLevel::HIGH,
            factors: new RiskFactors(80, 50, 0, 30),
            assessedAt: new \DateTimeImmutable('-6 months'),
        );

        $current = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 30,
            riskLevel: RiskLevel::LOW,
            factors: new RiskFactors(30, 20, 0, 30),
            assessedAt: new \DateTimeImmutable(),
        );

        $this->assertFalse($current->hasEscalatedFrom($previous));
    }

    public function test_has_sanctions_risk_returns_true_when_sanctions_score_positive(): void
    {
        $factors = new RiskFactors(
            jurisdictionScore: 40,
            businessTypeScore: 30,
            sanctionsScore: 50,
            transactionScore: 20,
        );

        $score = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $factors,
            assessedAt: new \DateTimeImmutable(),
        );

        $this->assertTrue($score->hasSanctionsRisk());
    }

    public function test_has_sanctions_risk_returns_false_when_sanctions_score_zero(): void
    {
        $factors = new RiskFactors(
            jurisdictionScore: 40,
            businessTypeScore: 30,
            sanctionsScore: 0,
            transactionScore: 20,
        );

        $score = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 30,
            riskLevel: RiskLevel::LOW,
            factors: $factors,
            assessedAt: new \DateTimeImmutable(),
        );

        $this->assertFalse($score->hasSanctionsRisk());
    }

    public function test_get_primary_risk_factor_returns_highest_factor(): void
    {
        $factors = new RiskFactors(
            jurisdictionScore: 30,
            businessTypeScore: 80,
            sanctionsScore: 10,
            transactionScore: 20,
        );

        $score = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $factors,
            assessedAt: new \DateTimeImmutable(),
        );

        $this->assertSame('business_type', $score->getPrimaryRiskFactor());
    }

    public function test_with_metadata_creates_new_instance_and_merges(): void
    {
        $original = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable(),
            metadata: ['existing_key' => 'existing_value'],
        );

        $updated = $original->withMetadata(['new_key' => 'new_value']);

        // New instance should be created
        $this->assertNotSame($original, $updated);

        // Original should be unchanged
        $this->assertArrayNotHasKey('new_key', $original->metadata);

        // Updated should have both keys
        $this->assertSame('existing_value', $updated->metadata['existing_key']);
        $this->assertSame('new_value', $updated->metadata['new_key']);
    }

    public function test_with_metadata_overwrites_existing_keys(): void
    {
        $original = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 50,
            riskLevel: RiskLevel::MEDIUM,
            factors: $this->createRiskFactors(),
            assessedAt: new \DateTimeImmutable(),
            metadata: ['key' => 'original_value'],
        );

        $updated = $original->withMetadata(['key' => 'updated_value']);

        $this->assertSame('updated_value', $updated->metadata['key']);
    }

    public function test_to_array_includes_all_expected_fields(): void
    {
        $factors = new RiskFactors(
            jurisdictionScore: 70,
            businessTypeScore: 50,
            sanctionsScore: 0,
            transactionScore: 40,
        );

        $score = new AmlRiskScore(
            partyId: 'party-123',
            overallScore: 65,
            riskLevel: RiskLevel::HIGH,
            factors: $factors,
            assessedAt: new \DateTimeImmutable('2024-01-15 10:30:00'),
            assessedBy: 'assessor-001',
            nextReviewDate: new \DateTimeImmutable('2024-04-15'),
            recommendations: ['Conduct EDD', 'Review transactions'],
            metadata: ['source' => 'automated'],
        );

        $array = $score->toArray();

        $this->assertSame('party-123', $array['party_id']);
        $this->assertSame(65, $array['overall_score']);
        $this->assertSame('high', $array['risk_level']);
        $this->assertIsString($array['risk_level_description']);
        $this->assertIsBool($array['requires_edd']);
        $this->assertIsBool($array['requires_enhanced_monitoring']);
        $this->assertIsArray($array['factors']);
        $this->assertIsString($array['primary_risk_factor']);
        $this->assertIsString($array['assessed_at']);
        $this->assertSame('assessor-001', $array['assessed_by']);
        $this->assertIsString($array['next_review_date']);
        $this->assertIsInt($array['days_until_review']);
        $this->assertIsBool($array['is_review_overdue']);
        $this->assertSame(['Conduct EDD', 'Review transactions'], $array['recommendations']);
        $this->assertSame(['source' => 'automated'], $array['metadata']);
    }

    public function test_from_array_hydrates_all_fields(): void
    {
        $data = [
            'party_id' => 'party-abc',
            'overall_score' => 55,
            'risk_level' => 'medium',
            'factors' => [
                'jurisdiction_score' => 40,
                'business_type_score' => 50,
                'sanctions_score' => 10,
                'transaction_score' => 30,
                'metadata' => [],
            ],
            'assessed_at' => '2024-02-20T09:00:00+00:00',
            'assessed_by' => 'system',
            'next_review_date' => '2024-05-20T09:00:00+00:00',
            'recommendations' => ['Monitor closely'],
            'metadata' => ['created_via' => 'import'],
        ];

        $score = AmlRiskScore::fromArray($data);

        $this->assertSame('party-abc', $score->partyId);
        $this->assertSame(55, $score->overallScore);
        $this->assertSame(RiskLevel::MEDIUM, $score->riskLevel);
        $this->assertSame(40, $score->factors->jurisdictionScore);
        $this->assertSame('system', $score->assessedBy);
        $this->assertNotNull($score->nextReviewDate);
        $this->assertSame(['Monitor closely'], $score->recommendations);
        $this->assertSame(['created_via' => 'import'], $score->metadata);
    }

    public function test_from_factors_generates_recommendations(): void
    {
        $factors = new RiskFactors(
            jurisdictionScore: 80,
            businessTypeScore: 75,
            sanctionsScore: 60,
            transactionScore: 70,
        );

        $score = AmlRiskScore::fromFactors(
            partyId: 'party-high-risk',
            factors: $factors,
            assessedBy: 'assessor',
        );

        $this->assertNotEmpty($score->recommendations);
        $this->assertIsArray($score->recommendations);
    }
}
