<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Enums;

use Nexus\ProcurementOperations\Enums\DuplicateMatchType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DuplicateMatchType::class)]
final class DuplicateMatchTypeTest extends TestCase
{
    public function test_exact_match_has_highest_confidence(): void
    {
        $this->assertEquals(1.0, DuplicateMatchType::EXACT_MATCH->getConfidenceLevel());
    }

    public function test_exact_match_has_critical_risk(): void
    {
        $this->assertEquals('critical', DuplicateMatchType::EXACT_MATCH->getRiskLevel());
    }

    public function test_exact_match_should_block_processing(): void
    {
        $this->assertTrue(DuplicateMatchType::EXACT_MATCH->shouldBlockProcessing());
    }

    public function test_invoice_number_match_should_block_processing(): void
    {
        $this->assertTrue(DuplicateMatchType::INVOICE_NUMBER_MATCH->shouldBlockProcessing());
    }

    public function test_amount_vendor_match_should_not_block(): void
    {
        $this->assertFalse(DuplicateMatchType::AMOUNT_VENDOR_MATCH->shouldBlockProcessing());
    }

    public function test_po_reference_match_should_not_block(): void
    {
        $this->assertFalse(DuplicateMatchType::PO_REFERENCE_MATCH->shouldBlockProcessing());
    }

    #[DataProvider('confidenceLevelProvider')]
    public function test_confidence_levels_are_in_expected_range(DuplicateMatchType $type, float $minConfidence): void
    {
        $this->assertGreaterThanOrEqual($minConfidence, $type->getConfidenceLevel());
        $this->assertLessThanOrEqual(1.0, $type->getConfidenceLevel());
    }

    /**
     * @return array<string, array{0: DuplicateMatchType, 1: float}>
     */
    public static function confidenceLevelProvider(): array
    {
        return [
            'exact_match' => [DuplicateMatchType::EXACT_MATCH, 0.95],
            'invoice_number_match' => [DuplicateMatchType::INVOICE_NUMBER_MATCH, 0.90],
            'amount_date_match' => [DuplicateMatchType::AMOUNT_DATE_MATCH, 0.80],
            'amount_vendor_match' => [DuplicateMatchType::AMOUNT_VENDOR_MATCH, 0.65],
            'fuzzy_invoice_number' => [DuplicateMatchType::FUZZY_INVOICE_NUMBER, 0.75],
            'po_reference_match' => [DuplicateMatchType::PO_REFERENCE_MATCH, 0.70],
            'hash_collision' => [DuplicateMatchType::HASH_COLLISION, 0.85],
        ];
    }

    #[DataProvider('riskLevelProvider')]
    public function test_risk_levels_are_valid(DuplicateMatchType $type, string $expectedRisk): void
    {
        $this->assertEquals($expectedRisk, $type->getRiskLevel());
    }

    /**
     * @return array<string, array{0: DuplicateMatchType, 1: string}>
     */
    public static function riskLevelProvider(): array
    {
        return [
            'exact_match' => [DuplicateMatchType::EXACT_MATCH, 'critical'],
            'invoice_number_match' => [DuplicateMatchType::INVOICE_NUMBER_MATCH, 'high'],
            'hash_collision' => [DuplicateMatchType::HASH_COLLISION, 'high'],
            'amount_date_match' => [DuplicateMatchType::AMOUNT_DATE_MATCH, 'medium'],
            'fuzzy_invoice_number' => [DuplicateMatchType::FUZZY_INVOICE_NUMBER, 'medium'],
            'po_reference_match' => [DuplicateMatchType::PO_REFERENCE_MATCH, 'low'],
            'amount_vendor_match' => [DuplicateMatchType::AMOUNT_VENDOR_MATCH, 'low'],
        ];
    }

    public function test_all_types_have_description(): void
    {
        foreach (DuplicateMatchType::cases() as $type) {
            $this->assertNotEmpty($type->getDescription());
            $this->assertIsString($type->getDescription());
        }
    }

    public function test_all_types_have_recommended_action(): void
    {
        foreach (DuplicateMatchType::cases() as $type) {
            $this->assertNotEmpty($type->getRecommendedAction());
            $this->assertIsString($type->getRecommendedAction());
        }
    }

    public function test_blocking_types_have_reject_or_hold_action(): void
    {
        foreach (DuplicateMatchType::cases() as $type) {
            if ($type->shouldBlockProcessing()) {
                $action = $type->getRecommendedAction();
                $this->assertTrue(
                    str_starts_with($action, 'REJECT:') || str_starts_with($action, 'HOLD:'),
                    "Blocking type {$type->value} should have REJECT or HOLD action"
                );
            }
        }
    }
}
