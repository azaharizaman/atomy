<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use Nexus\ProcurementOperations\Contracts\VendorRecommendationLlmInterface;
use Nexus\ProcurementOperations\Coordinators\VendorRecommendationCoordinator;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationCandidate;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;
use Nexus\ProcurementOperations\Services\DeterministicVendorScorer;
use Nexus\ProcurementOperations\Services\NullVendorRecommendationLlm;
use Nexus\ProcurementOperations\Tests\Support\FixedRecommendationClock;

#[CoversClass(VendorRecommendationCoordinator::class)]
final class VendorRecommendationCoordinatorTest extends TestCase
{
    #[Test]
    public function returnsDeterministicResultsWhenNoLlmIsAvailable(): void
    {
        $coordinator = new VendorRecommendationCoordinator(
            new DeterministicVendorScorer(new FixedRecommendationClock()),
            new NullVendorRecommendationLlm(),
        );

        $result = $coordinator->recommend($this->request());

        $this->assertSame('vendor-a', $result->candidates[0]->vendorId);
        $this->assertSame([], $result->candidates[0]->llmInsights);
        $this->assertContains('Category overlap: facilities.', $result->candidates[0]->deterministicReasons);
    }

    #[Test]
    public function llmMayEnrichExplanation(): void
    {
        $coordinator = new VendorRecommendationCoordinator(
            new DeterministicVendorScorer(new FixedRecommendationClock()),
            new FakeVendorRecommendationLlm([
                'vendor-a' => [
                    'score_delta' => 0,
                    'reason_summary' => 'Strong narrative fit for facility response.',
                    'insights' => ['Description mentions emergency maintenance, matching vendor profile.'],
                ],
            ]),
        );

        $result = $coordinator->recommend($this->request());

        $this->assertSame('Strong narrative fit for facility response.', $result->candidates[0]->recommendedReasonSummary);
        $this->assertSame(['Description mentions emergency maintenance, matching vendor profile.'], $result->candidates[0]->llmInsights);
    }

    #[Test]
    public function llmScoreAdjustmentIsBounded(): void
    {
        $baseline = (new DeterministicVendorScorer(new FixedRecommendationClock()))->score($this->request())->candidates[0]->fitScore;
        $coordinator = new VendorRecommendationCoordinator(
            new DeterministicVendorScorer(new FixedRecommendationClock()),
            new FakeVendorRecommendationLlm([
                'vendor-a' => ['score_delta' => 50],
            ]),
        );

        $result = $coordinator->recommend($this->request());

        $this->assertSame($baseline + VendorRecommendationCoordinator::MAX_LLM_SCORE_DELTA, $result->candidates[0]->fitScore);
    }

    #[Test]
    public function llmCannotIntroduceIneligibleOrUnknownVendors(): void
    {
        $coordinator = new VendorRecommendationCoordinator(
            new DeterministicVendorScorer(new FixedRecommendationClock()),
            new FakeVendorRecommendationLlm([
                'draft-vendor' => ['score_delta' => 10, 'reason_summary' => 'Should not surface.'],
                'unknown-vendor' => ['score_delta' => 10, 'reason_summary' => 'Should not surface.'],
            ]),
        );

        $result = $coordinator->recommend($this->request(includeDraft: true));

        $this->assertSame(['vendor-a'], array_map(static fn ($candidate): string => $candidate->vendorId, $result->candidates));
        $this->assertSame('draft-vendor', $result->excludedReasons[0]['vendor_id']);
    }

    private function request(bool $includeDraft = false): VendorRecommendationRequest
    {
        $candidates = [
            new VendorRecommendationCandidate(
                vendorId: 'vendor-a',
                vendorName: 'Facility Experts',
                status: 'approved',
                categories: ['facilities'],
                capabilities: ['emergency-maintenance'],
                regions: ['MY'],
            ),
        ];

        if ($includeDraft) {
            $candidates[] = new VendorRecommendationCandidate(
                vendorId: 'draft-vendor',
                vendorName: 'Draft Vendor',
                status: 'draft',
                categories: ['facilities'],
                regions: ['MY'],
            );
        }

        return new VendorRecommendationRequest(
            tenantId: 'tenant-1',
            rfqId: 'rfq-1',
            categories: ['facilities'],
            description: 'Emergency maintenance for Kuala Lumpur office.',
            geography: 'MY',
            spendBand: 'medium',
            lineItemSummary: ['maintenance response'],
            candidates: $candidates,
        );
    }
}

final readonly class FakeVendorRecommendationLlm implements VendorRecommendationLlmInterface
{
    /**
     * @param array<string, array<string, mixed>> $enrichments
     */
    public function __construct(private array $enrichments)
    {
    }

    public function enrich(VendorRecommendationRequest $request, array $candidates): array
    {
        return $this->enrichments;
    }
}
