<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Services;

use Nexus\QuotationIntelligence\DTOs\NormalizedQuoteLine;
use Nexus\QuotationIntelligence\Services\WeightedVendorScoringService;
use PHPUnit\Framework\TestCase;

final class WeightedVendorScoringServiceTest extends TestCase
{
    public function test_scores_and_ranks_vendors_with_mcda_and_lcc(): void
    {
        $service = new WeightedVendorScoringService();

        $vendorALine = new NormalizedQuoteLine(
            rfqLineId: 'rfq-line-1',
            vendorDescription: 'Laptop Pro',
            taxonomyCode: '43211503',
            quotedQuantity: 10.0,
            quotedUnit: 'EA',
            normalizedQuantity: 10.0,
            quotedUnitPrice: 900.0,
            normalizedUnitPrice: 900.0,
            aiConfidence: 0.95,
            snippets: [],
            metadata: [
                'lifecycle_multiplier' => 1.0,
                'sustainability_score' => 60,
                'commercial_terms' => ['lead_time_days' => 25],
            ]
        );

        $vendorBLine = new NormalizedQuoteLine(
            rfqLineId: 'rfq-line-1',
            vendorDescription: 'Notebook',
            taxonomyCode: '43211503',
            quotedQuantity: 10.0,
            quotedUnit: 'EA',
            normalizedQuantity: 10.0,
            quotedUnitPrice: 1000.0,
            normalizedUnitPrice: 1000.0,
            aiConfidence: 0.95,
            snippets: [],
            metadata: [
                'lifecycle_multiplier' => 1.2,
                'sustainability_score' => 80,
                'commercial_terms' => ['lead_time_days' => 20],
            ]
        );

        $result = $service->score('tenant-1', 'rfq-1', [
            [
                'vendor_id' => 'vendor-a',
                'lines' => [$vendorALine],
                'risks' => [],
            ],
            [
                'vendor_id' => 'vendor-b',
                'lines' => [$vendorBLine],
                'risks' => [
                    ['level' => 'high', 'message' => 'pricing anomaly'],
                ],
            ],
        ]);

        $this->assertCount(2, $result['ranking']);
        $this->assertSame('vendor-a', $result['ranking'][0]['vendor_id']);
        $this->assertSame(1, $result['ranking'][0]['rank']);
        $this->assertSame(9000.0, $result['ranking'][0]['dimensions']['lifecycle_cost_total']);
        $this->assertSame(12000.0, $result['ranking'][1]['dimensions']['lifecycle_cost_total']);
    }

    public function test_uses_default_values_when_optional_dimensions_missing(): void
    {
        $service = new WeightedVendorScoringService();

        $line = new NormalizedQuoteLine(
            rfqLineId: 'rfq-line-1',
            vendorDescription: 'Item',
            taxonomyCode: '10000000',
            quotedQuantity: 1.0,
            quotedUnit: 'EA',
            normalizedQuantity: 1.0,
            quotedUnitPrice: 100.0,
            normalizedUnitPrice: 100.0,
            aiConfidence: 0.9
        );

        $result = $service->score('tenant-1', 'rfq-1', [[
            'vendor_id' => 'vendor-a',
            'lines' => [$line],
            'risks' => [],
        ]]);

        $this->assertCount(1, $result['ranking']);
        $this->assertSame(50.0, $result['ranking'][0]['dimensions']['sustainability_score']);
        $this->assertSame(100.0, $result['ranking'][0]['dimensions']['delivery_score']);
    }

    public function test_handles_edge_values_for_lifecycle_multiplier_and_sustainability(): void
    {
        $service = new WeightedVendorScoringService();

        $line = new NormalizedQuoteLine(
            rfqLineId: 'rfq-line-1',
            vendorDescription: 'Item',
            taxonomyCode: '10000000',
            quotedQuantity: 2.0,
            quotedUnit: 'EA',
            normalizedQuantity: 2.0,
            quotedUnitPrice: 100.0,
            normalizedUnitPrice: 100.0,
            aiConfidence: 0.9,
            snippets: [],
            metadata: [
                'lifecycle_multiplier' => 0,
                'sustainability_score' => 120,
                'commercial_terms' => ['lead_time_days' => 0],
            ]
        );

        $result = $service->score('tenant-1', 'rfq-1', [[
            'vendor_id' => 'vendor-a',
            'lines' => [$line],
            'risks' => [['level' => 'low', 'message' => 'low']],
        ]]);

        $this->assertCount(1, $result['ranking']);
        $this->assertSame(200.0, $result['ranking'][0]['dimensions']['lifecycle_cost_total']);
        $this->assertSame(100.0, $result['ranking'][0]['dimensions']['sustainability_score']);
        $this->assertSame(95.0, $result['ranking'][0]['dimensions']['risk_score']);
    }
}
