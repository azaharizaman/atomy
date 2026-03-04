<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Services;

use Nexus\QuotationIntelligence\DTOs\NormalizedQuoteLine;
use Nexus\QuotationIntelligence\Services\QuoteComparisonMatrixService;
use PHPUnit\Framework\TestCase;

final class QuoteComparisonMatrixServiceTest extends TestCase
{
    public function test_builds_clusters_and_recommends_lowest_vendor(): void
    {
        $service = new QuoteComparisonMatrixService();

        $vendorALine = new NormalizedQuoteLine(
            rfqLineId: 'rfq-line-1',
            vendorDescription: 'Laptop Pro 16',
            taxonomyCode: '43211503',
            quotedQuantity: 10.0,
            quotedUnit: 'EA',
            normalizedQuantity: 10.0,
            quotedUnitPrice: 1200.0,
            normalizedUnitPrice: 1200.0,
            aiConfidence: 0.95
        );

        $vendorBLine = new NormalizedQuoteLine(
            rfqLineId: 'rfq-line-1',
            vendorDescription: 'Notebook 16',
            taxonomyCode: '43211503',
            quotedQuantity: 10.0,
            quotedUnit: 'EA',
            normalizedQuantity: 10.0,
            quotedUnitPrice: 1100.0,
            normalizedUnitPrice: 1100.0,
            aiConfidence: 0.95
        );

        $matrix = $service->buildMatrix('tenant-1', 'rfq-1', [
            ['vendor_id' => 'vendor-a', 'lines' => [$vendorALine]],
            ['vendor_id' => 'vendor-b', 'lines' => [$vendorBLine]],
        ]);

        $this->assertSame('tenant-1', $matrix['tenant_id']);
        $this->assertSame('rfq-1', $matrix['rfq_id']);
        $this->assertCount(1, $matrix['clusters']);
        $this->assertSame('rfq:rfq-line-1', $matrix['clusters'][0]['cluster_key']);
        $this->assertSame('vendor-b', $matrix['clusters'][0]['recommendation']['recommended_vendor_id']);
        $this->assertSame(1100.0, $matrix['clusters'][0]['statistics']['min_normalized_unit_price']);
    }

    public function test_falls_back_to_taxonomy_cluster_when_rfq_line_missing(): void
    {
        $service = new QuoteComparisonMatrixService();

        $line = new NormalizedQuoteLine(
            rfqLineId: '',
            vendorDescription: 'Portable Workstation',
            taxonomyCode: '43211503',
            quotedQuantity: 1.0,
            quotedUnit: 'EA',
            normalizedQuantity: 1.0,
            quotedUnitPrice: 999.0,
            normalizedUnitPrice: 999.0,
            aiConfidence: 0.88
        );

        $matrix = $service->buildMatrix('tenant-1', 'rfq-1', [
            ['vendor_id' => 'vendor-a', 'lines' => [$line]],
        ]);

        $this->assertSame('tax:43211503', $matrix['clusters'][0]['cluster_key']);
        $this->assertSame('taxonomy_code', $matrix['clusters'][0]['basis']);
    }

    public function test_skips_non_normalized_line_entries(): void
    {
        $service = new QuoteComparisonMatrixService();

        $matrix = $service->buildMatrix('tenant-1', 'rfq-1', [
            ['vendor_id' => 'vendor-a', 'lines' => ['not-a-dto']],
        ]);

        $this->assertSame([], $matrix['clusters']);
    }
}
