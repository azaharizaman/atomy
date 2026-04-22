<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationCandidate;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;
use Nexus\ProcurementOperations\Services\DeterministicVendorScorer;
use Nexus\ProcurementOperations\Tests\Support\FixedRecommendationClock;

#[CoversClass(DeterministicVendorScorer::class)]
final class DeterministicVendorScorerTest extends TestCase
{
    #[Test]
    public function categoryOverlapIncreasesScoreAndExplanation(): void
    {
        $result = (new DeterministicVendorScorer(new FixedRecommendationClock()))->score(new VendorRecommendationRequest(
            tenantId: 'tenant-1',
            rfqId: 'rfq-1',
            categories: ['industrial-pumps', 'maintenance'],
            description: 'Replacement pump maintenance services',
            geography: 'MY',
            spendBand: 'medium',
            lineItemSummary: ['industrial pump', 'seal kit'],
            candidates: [
                new VendorRecommendationCandidate(
                    vendorId: 'vendor-match',
                    vendorName: 'Pump Experts',
                    status: 'approved',
                    categories: ['industrial-pumps'],
                    capabilities: ['maintenance'],
                    regions: ['MY'],
                ),
                new VendorRecommendationCandidate(
                    vendorId: 'vendor-miss',
                    vendorName: 'Office Goods',
                    status: 'approved',
                    categories: ['office-supplies'],
                    capabilities: ['stationery'],
                    regions: ['MY'],
                ),
            ],
        ));

        $this->assertGreaterThan($result->candidates[1]->fitScore, $result->candidates[0]->fitScore);
        $this->assertContains('Category overlap: industrial-pumps.', $result->candidates[0]->deterministicReasons);
    }

    #[Test]
    public function geographyMismatchPenalizesAndWarns(): void
    {
        $result = (new DeterministicVendorScorer(new FixedRecommendationClock()))->score(new VendorRecommendationRequest(
            tenantId: 'tenant-1',
            rfqId: 'rfq-1',
            categories: ['logistics'],
            description: 'Malaysia logistics support',
            geography: 'MY',
            spendBand: 'low',
            lineItemSummary: ['freight'],
            candidates: [
                new VendorRecommendationCandidate(
                    vendorId: 'vendor-local',
                    vendorName: 'Local Logistics',
                    status: 'approved',
                    categories: ['logistics'],
                    regions: ['MY'],
                ),
                new VendorRecommendationCandidate(
                    vendorId: 'vendor-foreign',
                    vendorName: 'Foreign Logistics',
                    status: 'approved',
                    categories: ['logistics'],
                    regions: ['SG'],
                ),
            ],
        ));

        $this->assertGreaterThan($result->candidates[1]->fitScore, $result->candidates[0]->fitScore);
        $this->assertContains('weak_geography_match', $result->candidates[1]->warningFlags);
        $this->assertContains('Geography mismatch: requested MY, vendor covers SG.', $result->candidates[1]->warnings);
    }

    #[Test]
    public function recentActivityBoostsScore(): void
    {
        $result = (new DeterministicVendorScorer(new FixedRecommendationClock()))->score(new VendorRecommendationRequest(
            tenantId: 'tenant-1',
            rfqId: 'rfq-1',
            categories: ['it-services'],
            description: 'Managed service support',
            geography: 'MY',
            spendBand: 'medium',
            lineItemSummary: ['managed service'],
            candidates: [
                new VendorRecommendationCandidate(
                    vendorId: 'recent',
                    vendorName: 'Recent Vendor',
                    status: 'approved',
                    categories: ['it-services'],
                    regions: ['MY'],
                    lastActiveAt: new DateTimeImmutable('2026-04-12T00:00:00Z'),
                ),
                new VendorRecommendationCandidate(
                    vendorId: 'stale',
                    vendorName: 'Stale Vendor',
                    status: 'approved',
                    categories: ['it-services'],
                    regions: ['MY'],
                    lastActiveAt: new DateTimeImmutable('2025-01-01T00:00:00Z'),
                ),
            ],
        ));

        $this->assertGreaterThan($result->candidates[1]->fitScore, $result->candidates[0]->fitScore);
        $this->assertContains('Recent activity within 90 days.', $result->candidates[0]->deterministicReasons);
        $this->assertContains('sparse_historical_signal', $result->candidates[1]->warningFlags);
    }

    #[Test]
    public function nonApprovedVendorIsExcludedBeforeScoring(): void
    {
        $result = (new DeterministicVendorScorer(new FixedRecommendationClock()))->score(new VendorRecommendationRequest(
            tenantId: 'tenant-1',
            rfqId: 'rfq-1',
            categories: ['facilities'],
            description: 'Facilities maintenance',
            geography: 'MY',
            spendBand: 'low',
            lineItemSummary: ['maintenance'],
            candidates: [
                new VendorRecommendationCandidate(
                    vendorId: 'draft-vendor',
                    vendorName: 'Draft Vendor',
                    status: 'draft',
                    categories: ['facilities'],
                    regions: ['MY'],
                ),
            ],
        ));

        $this->assertSame([], $result->candidates);
        $this->assertSame([
            [
                'vendor_id' => 'draft-vendor',
                'vendor_name' => 'Draft Vendor',
                'reason' => 'Vendor status is draft; only approved vendors are eligible.',
            ],
        ], $result->excludedReasons);
    }

    #[Test]
    public function futureActivityDoesNotReceiveRecentActivityBoost(): void
    {
        $result = (new DeterministicVendorScorer(new FixedRecommendationClock()))->score(new VendorRecommendationRequest(
            tenantId: 'tenant-1',
            rfqId: 'rfq-1',
            categories: ['it-services'],
            description: 'Managed service support',
            geography: 'MY',
            spendBand: 'medium',
            lineItemSummary: ['managed service'],
            candidates: [
                new VendorRecommendationCandidate(
                    vendorId: 'future',
                    vendorName: 'Future Vendor',
                    status: 'approved',
                    categories: ['it-services'],
                    regions: ['MY'],
                    lastActiveAt: new DateTimeImmutable('2026-05-01T00:00:00Z'),
                ),
            ],
        ));

        $this->assertNotContains('Recent activity within 90 days.', $result->candidates[0]->deterministicReasons);
        $this->assertContains('sparse_historical_signal', $result->candidates[0]->warningFlags);
    }
}
