<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Tests\Unit\ValueObjects;

use Nexus\AmlCompliance\Enums\RiskLevel;
use Nexus\AmlCompliance\ValueObjects\RiskFactors;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RiskFactors::class)]
final class RiskFactorsTest extends TestCase
{
    public function test_constructor_sets_all_scores(): void
    {
        $factors = new RiskFactors(
            jurisdictionScore: 30,
            businessTypeScore: 40,
            sanctionsScore: 50,
            transactionScore: 60,
        );

        $this->assertSame(30, $factors->jurisdictionScore);
        $this->assertSame(40, $factors->businessTypeScore);
        $this->assertSame(50, $factors->sanctionsScore);
        $this->assertSame(60, $factors->transactionScore);
    }

    public function test_constructor_accepts_metadata(): void
    {
        $factors = new RiskFactors(
            jurisdictionScore: 30,
            businessTypeScore: 40,
            sanctionsScore: 50,
            transactionScore: 60,
            metadata: ['key' => 'value'],
        );

        $this->assertSame(['key' => 'value'], $factors->metadata);
    }

    public function test_constructor_throws_for_negative_jurisdiction_score(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RiskFactors(
            jurisdictionScore: -1,
            businessTypeScore: 40,
            sanctionsScore: 50,
            transactionScore: 60,
        );
    }

    public function test_constructor_throws_for_score_above_100(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RiskFactors(
            jurisdictionScore: 30,
            businessTypeScore: 101,
            sanctionsScore: 50,
            transactionScore: 60,
        );
    }

    public function test_zero_creates_all_zero_scores(): void
    {
        $factors = RiskFactors::zero();

        $this->assertSame(0, $factors->jurisdictionScore);
        $this->assertSame(0, $factors->businessTypeScore);
        $this->assertSame(0, $factors->sanctionsScore);
        $this->assertSame(0, $factors->transactionScore);
    }

    public function test_from_array_creates_instance(): void
    {
        $factors = RiskFactors::fromArray([
            'jurisdictionScore' => 25,
            'businessTypeScore' => 35,
            'sanctionsScore' => 45,
            'transactionScore' => 55,
        ]);

        $this->assertSame(25, $factors->jurisdictionScore);
        $this->assertSame(35, $factors->businessTypeScore);
        $this->assertSame(45, $factors->sanctionsScore);
        $this->assertSame(55, $factors->transactionScore);
    }

    public function test_from_array_supports_snake_case_keys(): void
    {
        $factors = RiskFactors::fromArray([
            'jurisdiction_score' => 25,
            'business_type_score' => 35,
            'sanctions_score' => 45,
            'transaction_score' => 55,
        ]);

        $this->assertSame(25, $factors->jurisdictionScore);
        $this->assertSame(35, $factors->businessTypeScore);
    }

    public function test_calculate_composite_score(): void
    {
        $factors = new RiskFactors(
            jurisdictionScore: 100,
            businessTypeScore: 100,
            sanctionsScore: 100,
            transactionScore: 100,
        );

        // All 100 with weights should give 100
        $this->assertSame(100, $factors->calculateCompositeScore());
    }

    public function test_calculate_composite_score_with_zero(): void
    {
        $factors = RiskFactors::zero();

        $this->assertSame(0, $factors->calculateCompositeScore());
    }

    public function test_get_risk_level(): void
    {
        // Low score → LOW risk
        $lowFactors = new RiskFactors(10, 10, 10, 10);
        $this->assertSame(RiskLevel::LOW, $lowFactors->getRiskLevel());

        // High score → HIGH risk
        $highFactors = new RiskFactors(100, 100, 100, 100);
        $this->assertSame(RiskLevel::HIGH, $highFactors->getRiskLevel());
    }

    public function test_get_max_score(): void
    {
        $factors = new RiskFactors(30, 60, 50, 40);

        $this->assertSame(60, $factors->getMaxScore());
    }

    public function test_get_highest_risk_factor(): void
    {
        $factors = new RiskFactors(30, 60, 50, 40);

        // Returns snake_case: 'business_type' because businessTypeScore is highest
        $this->assertSame('business_type', $factors->getHighestRiskFactor());
    }

    public function test_get_highest_risk_factor_with_jurisdiction_highest(): void
    {
        $factors = new RiskFactors(90, 60, 50, 40);

        $this->assertSame('jurisdiction', $factors->getHighestRiskFactor());
    }

    public function test_to_array_returns_structured_data(): void
    {
        $factors = new RiskFactors(30, 40, 50, 60);

        $array = $factors->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('jurisdiction_score', $array);
        $this->assertArrayHasKey('business_type_score', $array);
        $this->assertArrayHasKey('sanctions_score', $array);
        $this->assertArrayHasKey('transaction_score', $array);
        $this->assertArrayHasKey('composite_score', $array);
    }

    public function test_weight_constants_sum_to_one(): void
    {
        $sum = RiskFactors::WEIGHT_JURISDICTION
             + RiskFactors::WEIGHT_BUSINESS_TYPE
             + RiskFactors::WEIGHT_SANCTIONS
             + RiskFactors::WEIGHT_TRANSACTION;

        $this->assertEqualsWithDelta(1.0, $sum, 0.0001);
    }

    public function test_get_factors_above_threshold_returns_elevated_factors(): void
    {
        $factors = new RiskFactors(
            jurisdictionScore: 80,
            businessTypeScore: 30,
            sanctionsScore: 90,
            transactionScore: 40,
        );

        $elevated = $factors->getFactorsAboveThreshold(50);

        $this->assertCount(2, $elevated);
        $this->assertArrayHasKey('jurisdiction', $elevated);
        $this->assertArrayHasKey('sanctions', $elevated);
        $this->assertSame(80, $elevated['jurisdiction']);
        $this->assertSame(90, $elevated['sanctions']);
    }

    public function test_get_factors_above_threshold_returns_empty_when_none_exceed(): void
    {
        $factors = new RiskFactors(10, 20, 30, 40);

        $elevated = $factors->getFactorsAboveThreshold(50);

        $this->assertEmpty($elevated);
    }

    public function test_has_high_risk_factor_true_when_any_factor_exceeds_70(): void
    {
        $factors = new RiskFactors(
            jurisdictionScore: 20,
            businessTypeScore: 70,
            sanctionsScore: 30,
            transactionScore: 40,
        );

        $this->assertTrue($factors->hasHighRiskFactor());
    }

    public function test_has_high_risk_factor_false_when_all_below_70(): void
    {
        $factors = new RiskFactors(
            jurisdictionScore: 20,
            businessTypeScore: 50,
            sanctionsScore: 30,
            transactionScore: 60,
        );

        $this->assertFalse($factors->hasHighRiskFactor());
    }

    public function test_has_sanctions_risk_true_when_sanctions_above_zero(): void
    {
        $factors = new RiskFactors(
            jurisdictionScore: 0,
            businessTypeScore: 0,
            sanctionsScore: 50,
            transactionScore: 0,
        );

        $this->assertTrue($factors->hasSanctionsRisk());
    }

    public function test_has_sanctions_risk_false_when_sanctions_is_zero(): void
    {
        $factors = new RiskFactors(
            jurisdictionScore: 50,
            businessTypeScore: 50,
            sanctionsScore: 0,
            transactionScore: 50,
        );

        $this->assertFalse($factors->hasSanctionsRisk());
    }

    public function test_with_jurisdiction_score_creates_new_instance(): void
    {
        $original = new RiskFactors(30, 40, 50, 60);
        $updated = $original->withJurisdictionScore(80);

        $this->assertSame(30, $original->jurisdictionScore);
        $this->assertSame(80, $updated->jurisdictionScore);
        $this->assertSame(40, $updated->businessTypeScore);
        $this->assertSame(50, $updated->sanctionsScore);
        $this->assertSame(60, $updated->transactionScore);
    }

    public function test_with_business_type_score_creates_new_instance(): void
    {
        $original = new RiskFactors(30, 40, 50, 60);
        $updated = $original->withBusinessTypeScore(90);

        $this->assertSame(40, $original->businessTypeScore);
        $this->assertSame(90, $updated->businessTypeScore);
    }

    public function test_with_sanctions_score_creates_new_instance(): void
    {
        $original = new RiskFactors(30, 40, 50, 60);
        $updated = $original->withSanctionsScore(100);

        $this->assertSame(50, $original->sanctionsScore);
        $this->assertSame(100, $updated->sanctionsScore);
    }

    public function test_with_transaction_score_creates_new_instance(): void
    {
        $original = new RiskFactors(30, 40, 50, 60);
        $updated = $original->withTransactionScore(25);

        $this->assertSame(60, $original->transactionScore);
        $this->assertSame(25, $updated->transactionScore);
    }

    public function test_with_metadata_merges_metadata(): void
    {
        $original = new RiskFactors(30, 40, 50, 60, metadata: ['key1' => 'value1']);
        $updated = $original->withMetadata(['key2' => 'value2']);

        $this->assertSame(['key1' => 'value1'], $original->metadata);
        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $updated->metadata);
    }

    public function test_get_highest_risk_factor_with_sanctions_highest(): void
    {
        $factors = new RiskFactors(30, 40, 95, 50);

        $this->assertSame('sanctions', $factors->getHighestRiskFactor());
    }

    public function test_get_highest_risk_factor_with_transaction_highest(): void
    {
        $factors = new RiskFactors(30, 40, 50, 99);

        $this->assertSame('transaction', $factors->getHighestRiskFactor());
    }

    public function test_calculate_composite_score_weighted_correctly(): void
    {
        // Jurisdiction: 100 * 0.30 = 30
        // BusinessType: 0 * 0.20 = 0
        // Sanctions: 0 * 0.25 = 0
        // Transaction: 0 * 0.25 = 0
        // Total: 30
        $factors = new RiskFactors(100, 0, 0, 0);
        $this->assertSame(30, $factors->calculateCompositeScore());

        // BusinessType only: 100 * 0.20 = 20
        $factors2 = new RiskFactors(0, 100, 0, 0);
        $this->assertSame(20, $factors2->calculateCompositeScore());

        // Sanctions only: 100 * 0.25 = 25
        $factors3 = new RiskFactors(0, 0, 100, 0);
        $this->assertSame(25, $factors3->calculateCompositeScore());

        // Transaction only: 100 * 0.25 = 25
        $factors4 = new RiskFactors(0, 0, 0, 100);
        $this->assertSame(25, $factors4->calculateCompositeScore());
    }

    public function test_from_array_handles_missing_metadata(): void
    {
        $factors = RiskFactors::fromArray([
            'jurisdictionScore' => 25,
            'businessTypeScore' => 35,
            'sanctionsScore' => 45,
            'transactionScore' => 55,
            // metadata not provided
        ]);

        $this->assertSame([], $factors->metadata);
    }

    public function test_constructor_throws_for_negative_transaction_score(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RiskFactors(30, 40, 50, -1);
    }

    public function test_constructor_throws_for_sanctions_score_above_100(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RiskFactors(30, 40, 101, 60);
    }

    public function test_to_array_includes_risk_level(): void
    {
        $factors = new RiskFactors(80, 80, 80, 80);

        $array = $factors->toArray();

        $this->assertArrayHasKey('risk_level', $array);
        $this->assertSame('high', $array['risk_level']);
    }

    public function test_to_array_includes_highest_factor(): void
    {
        $factors = new RiskFactors(30, 60, 50, 40);

        $array = $factors->toArray();

        $this->assertArrayHasKey('highest_factor', $array);
        $this->assertSame('business_type', $array['highest_factor']);
    }
}
