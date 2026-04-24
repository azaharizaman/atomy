<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Coordinators;

use Nexus\MachineLearning\Enums\AiEndpointGroup;
use Nexus\ProcurementML\ValueObjects\ProviderAiProvenance;
use Nexus\ProcurementML\ValueObjects\VendorRecommendationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Nexus\ProcurementOperations\Contracts\VendorRecommendationLlmInterface;
use Nexus\ProcurementOperations\Coordinators\VendorRecommendationCoordinator;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationCandidate;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;
use Nexus\ProcurementOperations\Services\DeterministicVendorScorer;
use Nexus\ProcurementOperations\Tests\Support\FixedRecommendationClock;

#[CoversClass(VendorRecommendationCoordinator::class)]
final class VendorRecommendationCoordinatorTest extends TestCase
{
    #[Test]
    public function providerBackedInferenceRanksOnlyDeterministicEligibleCandidates(): void
    {
        $coordinator = new VendorRecommendationCoordinator(
            new DeterministicVendorScorer(new FixedRecommendationClock()),
            new FakeVendorRecommendationLlm([
                'eligible_candidates' => [
                    [
                        'vendor_id' => 'vendor-a',
                        'vendor_name' => 'Facility Experts',
                        'provider_explanation' => 'Provider ranked vendor-a first.',
                        'llm_insights' => ['High confidence in category fit.'],
                    ],
                ],
                'provider_explanation' => 'Provider ranked the only approved vendor.',
                'provenance' => $this->provenance()->toArray(),
            ]),
        );

        $result = $coordinator->recommend($this->request());

        self::assertInstanceOf(VendorRecommendationResult::class, $result);
        self::assertTrue($result->isAvailable());
        self::assertSame(['vendor-a'], array_map(static fn ($candidate): string => $candidate->vendorId, $result->eligibleCandidates));
        self::assertSame(['draft-vendor'], array_map(static fn ($candidate): string => $candidate->vendorId, $result->excludedCandidates));
        self::assertSame('Provider ranked the only approved vendor.', $result->providerExplanation);
        self::assertSame('vendor-a', $result->eligibleCandidates[0]->vendorId);
        self::assertSame(['High confidence in category fit.'], $result->eligibleCandidates[0]->llmInsights);
        self::assertSame($this->provenance()->toArray(), $result->provenance?->toArray());
    }

    #[Test]
    public function zeroCandidateProviderResponsesRemainAvailable(): void
    {
        $coordinator = new VendorRecommendationCoordinator(
            new DeterministicVendorScorer(new FixedRecommendationClock()),
            new FakeVendorRecommendationLlm([
                'eligible_candidates' => [],
                'provider_explanation' => 'No approved vendors met the provider threshold.',
                'provenance' => $this->provenance()->toArray(),
            ]),
        );

        $result = $coordinator->recommend($this->request());

        self::assertTrue($result->isAvailable());
        self::assertSame([], $result->eligibleCandidates);
        self::assertSame('No approved vendors met the provider threshold.', $result->providerExplanation);
        self::assertSame($this->provenance()->toArray(), $result->provenance?->toArray());
    }

    #[Test]
    public function providerOutputThatIntroducesUnknownVendorsIsRejected(): void
    {
        $coordinator = new VendorRecommendationCoordinator(
            new DeterministicVendorScorer(new FixedRecommendationClock()),
            new FakeVendorRecommendationLlm([
                'eligible_candidates' => [
                    [
                        'vendor_id' => 'unknown-vendor',
                        'provider_explanation' => 'Should not surface.',
                    ],
                ],
                'provider_explanation' => 'Invented vendor.',
                'provenance' => $this->provenance()->toArray(),
            ]),
        );

        $result = $coordinator->recommend($this->request());

        self::assertFalse($result->isAvailable());
        self::assertSame('provider_output_rejected', $result->unavailableReason);
        self::assertSame([], $result->eligibleCandidates);
        self::assertSame([], $result->excludedCandidates);
    }

    private function request(): VendorRecommendationRequest
    {
        return new VendorRecommendationRequest(
            tenantId: 'tenant-1',
            rfqId: 'rfq-1',
            categories: ['facilities'],
            description: 'Emergency maintenance for Kuala Lumpur office.',
            geography: 'MY',
            spendBand: 'medium',
            lineItemSummary: ['maintenance response'],
            candidates: [
                new VendorRecommendationCandidate(
                    vendorId: 'vendor-a',
                    vendorName: 'Facility Experts',
                    status: 'approved',
                    categories: ['facilities'],
                    capabilities: ['emergency-maintenance'],
                    regions: ['MY'],
                ),
                new VendorRecommendationCandidate(
                    vendorId: 'draft-vendor',
                    vendorName: 'Draft Vendor',
                    status: 'draft',
                    categories: ['facilities'],
                    regions: ['MY'],
                ),
            ],
        );
    }

    private function provenance(): ProviderAiProvenance
    {
        return new ProviderAiProvenance(
            providerName: 'openrouter',
            endpointGroup: AiEndpointGroup::SOURCING_RECOMMENDATION,
            modelRevision: 'openai/gpt-4.1-mini:2026-04-01',
            promptTemplateVersion: 'vendor-ranking@2026-04-24',
            requestTraceId: 'trace-vendor-123',
            inputHash: 'sha256:input',
            outputHash: 'sha256:output',
            latencyMs: 653,
            confidence: 0.91,
            reliabilityHints: ['provider_confidence' => 'high'],
            processedAt: new \DateTimeImmutable('2026-04-24T09:30:00+08:00'),
        );
    }
}

final readonly class FakeVendorRecommendationLlm implements VendorRecommendationLlmInterface
{
    /**
     * @param array<string, mixed> $response
     */
    public function __construct(private array $response)
    {
    }

    public function enrich(VendorRecommendationRequest $request, array $candidates): array
    {
        return $this->response;
    }
}
