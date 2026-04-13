<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Services\VendorSelectionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorSelectionService::class)]
final class VendorSelectionServiceTest extends TestCase
{
    private VendorSelectionService $service;

    protected function setUp(): void
    {
        $this->service = new VendorSelectionService();
    }

    #[Test]
    public function it_calculates_vendor_score_with_default_weights(): void
    {
        $factors = [
            'price_competitiveness' => 0.8,
            'delivery_reliability' => 0.9,
            'quality_rating' => 0.7,
            'payment_terms_score' => 1.0,
            'relationship_score' => 0.6,
        ];

        $score = $this->service->calculateVendorScore($factors);

        $expected = (0.8 * 0.30) + (0.9 * 0.25) + (0.7 * 0.25) + (1.0 * 0.10) + (0.6 * 0.10);
        $expected = ($expected / 1.0) * 100;

        $this->assertEqualsWithDelta($expected, $score, 0.01);
    }

    #[Test]
    public function it_calculates_vendor_score_with_custom_weights(): void
    {
        $factors = [
            'price_competitiveness' => 0.8,
            'delivery_reliability' => 0.9,
            'quality_rating' => 0.7,
        ];

        $weights = [
            'price_competitiveness' => 0.50,
            'delivery_reliability' => 0.30,
            'quality_rating' => 0.20,
        ];

        $score = $this->service->calculateVendorScore($factors, $weights);

        // 0.8*0.5 + 0.9*0.3 + 0.7*0.2 = 0.4 + 0.27 + 0.14 = 0.81, normalized to 81.0
        $expected = 81.0;

        $this->assertEqualsWithDelta($expected, $score, 0.01);
    }

    #[Test]
    public function it_returns_zero_for_empty_factors(): void
    {
        $score = $this->service->calculateVendorScore([]);

        $this->assertSame(0.0, $score);
    }

    #[Test]
    public function it_returns_zero_when_weights_sum_to_zero(): void
    {
        $factors = [
            'price_competitiveness' => 0.8,
        ];

        $weights = [
            'price_competitiveness' => 0.0,
        ];

        $score = $this->service->calculateVendorScore($factors, $weights);

        $this->assertSame(0.0, $score);
    }

    #[Test]
    public function it_calculates_score_only_for_factors_in_weights(): void
    {
        $factors = [
            'price_competitiveness' => 0.8,
            'delivery_reliability' => 0.9,
            'quality_rating' => 0.7,
            'payment_terms_score' => 1.0,
            'relationship_score' => 0.6,
        ];

        $weights = [
            'price_competitiveness' => 0.60,
            'quality_rating' => 0.40,
        ];

        $score = $this->service->calculateVendorScore($factors, $weights);

        // With merged weights (custom overrides + defaults): 0.8*0.6 + 0.9*0.25 + 0.7*0.4 + 1.0*0.1 + 0.6*0.1 = 1.145
        // Total weight = 0.6 + 0.25 + 0.4 + 0.1 + 0.1 = 1.45
        // normalized = 1.145 / 1.45 * 100 = ~78.97
        $expected = 78.97;

        $this->assertEqualsWithDelta($expected, $score, 0.01);
    }

    #[Test]
    public function it_selects_best_vendor_by_score(): void
    {
        $candidates = [
            [
                'vendor_id' => 'V1',
                'score' => 75.0,
                'unit_price' => Money::of(100, 'USD'),
                'lead_time_days' => 5,
                'meets_requirements' => true,
            ],
            [
                'vendor_id' => 'V2',
                'score' => 85.0,
                'unit_price' => Money::of(110, 'USD'),
                'lead_time_days' => 3,
                'meets_requirements' => true,
            ],
            [
                'vendor_id' => 'V3',
                'score' => 65.0,
                'unit_price' => Money::of(90, 'USD'),
                'lead_time_days' => 7,
                'meets_requirements' => true,
            ],
        ];

        $selected = $this->service->selectBestVendor($candidates);

        $this->assertSame('V2', $selected);
    }

    #[Test]
    public function it_selects_best_vendor_by_lowest_price(): void
    {
        $candidates = [
            [
                'vendor_id' => 'V1',
                'score' => 85.0,
                'unit_price' => Money::of(100, 'USD'),
                'lead_time_days' => 5,
                'meets_requirements' => true,
            ],
            [
                'vendor_id' => 'V2',
                'score' => 75.0,
                'unit_price' => Money::of(90, 'USD'),
                'lead_time_days' => 3,
                'meets_requirements' => true,
            ],
        ];

        $selected = $this->service->selectBestVendor($candidates, ['prefer_lowest_price' => true]);

        $this->assertSame('V2', $selected);
    }

    #[Test]
    public function it_returns_null_when_no_candidates_qualify(): void
    {
        $candidates = [
            [
                'vendor_id' => 'V1',
                'score' => 50.0,
                'unit_price' => Money::of(100, 'USD'),
                'lead_time_days' => 5,
                'meets_requirements' => false,
            ],
        ];

        $selected = $this->service->selectBestVendor($candidates);

        $this->assertNull($selected);
    }

    #[Test]
    public function it_filters_candidates_by_minimum_score(): void
    {
        $candidates = [
            [
                'vendor_id' => 'V1',
                'score' => 70.0,
                'unit_price' => Money::of(100, 'USD'),
                'lead_time_days' => 5,
                'meets_requirements' => true,
            ],
            [
                'vendor_id' => 'V2',
                'score' => 55.0,
                'unit_price' => Money::of(90, 'USD'),
                'lead_time_days' => 3,
                'meets_requirements' => true,
            ],
        ];

        $selected = $this->service->selectBestVendor($candidates, ['minimum_score' => 65.0]);

        $this->assertSame('V1', $selected);
    }

    #[Test]
    public function it_filters_candidates_by_max_lead_time(): void
    {
        $candidates = [
            [
                'vendor_id' => 'V1',
                'score' => 85.0,
                'unit_price' => Money::of(100, 'USD'),
                'lead_time_days' => 5,
                'meets_requirements' => true,
            ],
            [
                'vendor_id' => 'V2',
                'score' => 80.0,
                'unit_price' => Money::of(90, 'USD'),
                'lead_time_days' => 10,
                'meets_requirements' => true,
            ],
        ];

        $selected = $this->service->selectBestVendor($candidates, ['maximum_lead_time_days' => 7]);

        $this->assertSame('V1', $selected);
    }

    #[Test]
    public function it_returns_null_when_all_filtered_out(): void
    {
        $candidates = [
            [
                'vendor_id' => 'V1',
                'score' => 85.0,
                'unit_price' => Money::of(100, 'USD'),
                'lead_time_days' => 5,
                'meets_requirements' => true,
            ],
        ];

        $selected = $this->service->selectBestVendor($candidates, [
            'minimum_score' => 90.0,
            'maximum_lead_time_days' => 2,
        ]);

        $this->assertNull($selected);
    }

    #[Test]
    public function it_ranks_vendors_by_value(): void
    {
        $vendors = [
            [
                'vendor_id' => 'V1',
                'score' => 90.0,
                'unit_price' => Money::of(100, 'USD'),
                'lead_time_days' => 3,
            ],
            [
                'vendor_id' => 'V2',
                'score' => 80.0,
                'unit_price' => Money::of(80, 'USD'),
                'lead_time_days' => 5,
            ],
            [
                'vendor_id' => 'V3',
                'score' => 85.0,
                'unit_price' => Money::of(90, 'USD'),
                'lead_time_days' => 4,
            ],
        ];

        $ranked = $this->service->rankVendorsByValue($vendors);

        $this->assertCount(3, $ranked);
        $this->assertSame(1, $ranked[0]['rank']);
        $this->assertSame(2, $ranked[1]['rank']);
        $this->assertSame(3, $ranked[2]['rank']);
    }

    #[Test]
    public function it_returns_empty_array_for_empty_vendors(): void
    {
        $ranked = $this->service->rankVendorsByValue([]);

        $this->assertSame([], $ranked);
    }

    #[Test]
    public function it_calculates_value_scores_correctly(): void
    {
        $vendors = [
            [
                'vendor_id' => 'V1',
                'score' => 100.0,
                'unit_price' => Money::of(100, 'USD'),
                'lead_time_days' => 1,
            ],
            [
                'vendor_id' => 'V2',
                'score' => 50.0,
                'unit_price' => Money::of(200, 'USD'),
                'lead_time_days' => 10,
            ],
        ];

        $ranked = $this->service->rankVendorsByValue($vendors);

        $this->assertGreaterThan($ranked[1]['value_score'], $ranked[0]['value_score']);
    }

    #[Test]
    public function it_handles_vendors_with_same_price(): void
    {
        $vendors = [
            [
                'vendor_id' => 'V1',
                'score' => 90.0,
                'unit_price' => Money::of(100, 'USD'),
                'lead_time_days' => 3,
            ],
            [
                'vendor_id' => 'V2',
                'score' => 80.0,
                'unit_price' => Money::of(100, 'USD'),
                'lead_time_days' => 5,
            ],
        ];

        $ranked = $this->service->rankVendorsByValue($vendors);

        $this->assertSame('V1', $ranked[0]['vendor_id']);
    }

    #[Test]
    public function it_checks_vendor_qualification_passes(): void
    {
        $vendorProfile = [
            'is_active' => true,
            'is_approved' => true,
            'performance_score' => 75.0,
            'credit_status' => 'good',
            'certifications' => ['ISO9001'],
        ];

        $result = $this->service->checkVendorQualification($vendorProfile);

        $this->assertTrue($result['qualifies']);
        $this->assertSame([], $result['reasons']);
    }

    #[Test]
    public function it_fails_qualification_for_inactive_vendor(): void
    {
        $vendorProfile = [
            'is_active' => false,
            'is_approved' => true,
            'performance_score' => 75.0,
            'credit_status' => 'good',
            'certifications' => ['ISO9001'],
        ];

        $result = $this->service->checkVendorQualification($vendorProfile);

        $this->assertFalse($result['qualifies']);
        $this->assertContains('Vendor is not active', $result['reasons']);
    }

    #[Test]
    public function it_fails_qualification_for_unapproved_vendor(): void
    {
        $vendorProfile = [
            'is_active' => true,
            'is_approved' => false,
            'performance_score' => 75.0,
            'credit_status' => 'good',
            'certifications' => ['ISO9001'],
        ];

        $result = $this->service->checkVendorQualification($vendorProfile);

        $this->assertFalse($result['qualifies']);
        $this->assertContains('Vendor is not approved', $result['reasons']);
    }

    #[Test]
    public function it_fails_qualification_for_poor_credit_status(): void
    {
        $vendorProfile = [
            'is_active' => true,
            'is_approved' => true,
            'performance_score' => 75.0,
            'credit_status' => 'poor',
            'certifications' => ['ISO9001'],
        ];

        $result = $this->service->checkVendorQualification($vendorProfile);

        $this->assertFalse($result['qualifies']);
        $this->assertCount(1, $result['reasons']);
    }

    #[Test]
    public function it_fails_qualification_for_low_performance_score(): void
    {
        $vendorProfile = [
            'is_active' => true,
            'is_approved' => true,
            'performance_score' => 40.0,
            'credit_status' => 'good',
            'certifications' => ['ISO9001'],
        ];

        $result = $this->service->checkVendorQualification($vendorProfile, [
            'minimum_performance_score' => 50.0,
        ]);

        $this->assertFalse($result['qualifies']);
        $this->assertStringContainsString('Performance score', $result['reasons'][0]);
    }

    #[Test]
    public function it_fails_qualification_for_missing_certifications(): void
    {
        $vendorProfile = [
            'is_active' => true,
            'is_approved' => true,
            'performance_score' => 75.0,
            'credit_status' => 'good',
            'certifications' => [],
        ];

        $result = $this->service->checkVendorQualification($vendorProfile, [
            'required_certifications' => ['ISO9001', 'ISO14001'],
        ]);

        $this->assertFalse($result['qualifies']);
        $this->assertStringContainsString('Missing required certifications', $result['reasons'][0]);
    }

    #[Test]
    public function it_fails_qualification_with_custom_credit_status_allowlist(): void
    {
        $vendorProfile = [
            'is_active' => true,
            'is_approved' => true,
            'performance_score' => 75.0,
            'credit_status' => 'good',
            'certifications' => ['ISO9001'],
        ];

        $result = $this->service->checkVendorQualification($vendorProfile, [
            'allowed_credit_statuses' => ['excellent'],
        ]);

        $this->assertFalse($result['qualifies']);
        $this->assertStringContainsString('not in allowed statuses', $result['reasons'][0]);
    }

    #[Test]
    public function it_collects_all_failure_reasons(): void
    {
        $vendorProfile = [
            'is_active' => false,
            'is_approved' => false,
            'performance_score' => 30.0,
            'credit_status' => 'poor',
            'certifications' => [],
        ];

        $result = $this->service->checkVendorQualification($vendorProfile);

        $this->assertFalse($result['qualifies']);
        $this->assertCount(4, $result['reasons']);
    }

    private function assertFloatEquals(float $expected, float $actual, float $delta): void
    {
        $this->assertTrue(
            abs($expected - $actual) <= $delta,
            sprintf('Expected %.2f to equal %.2f within delta %.2f', $actual, $expected, $delta)
        );
    }
}