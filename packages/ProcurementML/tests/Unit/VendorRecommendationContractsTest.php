<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Tests\Unit;

use DateTimeImmutable;
use Nexus\MachineLearning\Enums\AiEndpointGroup;
use Nexus\ProcurementML\Enums\VendorRecommendationResultStatus;
use Nexus\ProcurementML\ValueObjects\ProviderAiProvenance;
use Nexus\ProcurementML\ValueObjects\VendorRecommendationEligibleCandidate;
use Nexus\ProcurementML\ValueObjects\VendorRecommendationExcludedCandidate;
use Nexus\ProcurementML\ValueObjects\VendorRecommendationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorRecommendationResult::class)]
final class VendorRecommendationContractsTest extends TestCase
{
    #[Test]
    public function it_distinguishes_zero_candidate_success_from_unavailable_ranking(): void
    {
        $provenance = $this->provenance();

        $available = VendorRecommendationResult::available(
            tenantId: 'tenant-1',
            rfqId: 'rfq-1',
            eligibleCandidates: [],
            excludedCandidates: [],
            providerExplanation: 'Provider returned no eligible vendors for this RFQ.',
            deterministicReasonSet: [],
            provenance: $provenance,
        );

        $unavailable = VendorRecommendationResult::unavailable(
            tenantId: 'tenant-1',
            rfqId: 'rfq-1',
            reason: 'vendor_ai_ranking_unavailable',
        );

        self::assertTrue($available->isAvailable());
        self::assertSame(VendorRecommendationResultStatus::AVAILABLE, $available->status);
        self::assertSame([], $available->eligibleCandidates);
        self::assertSame([], $available->excludedCandidates);
        self::assertSame($provenance, $available->provenance);
        self::assertFalse($unavailable->isAvailable());
        self::assertSame(VendorRecommendationResultStatus::UNAVAILABLE, $unavailable->status);
        self::assertSame('vendor_ai_ranking_unavailable', $unavailable->unavailableReason);
        self::assertSame([], $unavailable->eligibleCandidates);
        self::assertSame([], $unavailable->excludedCandidates);
    }

    #[Test]
    public function it_serializes_eligible_and_excluded_candidates_separately(): void
    {
        $provenance = $this->provenance();

        $result = VendorRecommendationResult::available(
            tenantId: 'tenant-1',
            rfqId: 'rfq-1',
            eligibleCandidates: [
                new VendorRecommendationEligibleCandidate(
                    vendorId: 'vendor-1',
                    vendorName: 'Alpha Supply',
                    fitScore: 87,
                    confidenceBand: 'high',
                    providerExplanation: 'Ranked first by the provider.',
                    deterministicReasons: ['Category overlap: facilities.'],
                    llmInsights: ['Strong regional coverage.'],
                    warningFlags: ['fresh_profile'],
                    warnings: [],
                ),
            ],
            excludedCandidates: [
                new VendorRecommendationExcludedCandidate(
                    vendorId: 'vendor-2',
                    vendorName: 'Beta Supply',
                    reason: 'Vendor status is suspended; only approved vendors are eligible.',
                ),
            ],
            providerExplanation: 'Ranked first by the provider.',
            deterministicReasonSet: ['Category overlap: facilities.'],
            provenance: $provenance,
        );

        self::assertSame(
            [
                'tenant_id' => 'tenant-1',
                'rfq_id' => 'rfq-1',
                'status' => 'available',
                'eligible_candidates' => [
                    [
                        'vendor_id' => 'vendor-1',
                        'vendor_name' => 'Alpha Supply',
                        'fit_score' => 87,
                        'confidence_band' => 'high',
                        'provider_explanation' => 'Ranked first by the provider.',
                        'deterministic_reasons' => ['Category overlap: facilities.'],
                        'llm_insights' => ['Strong regional coverage.'],
                        'warning_flags' => ['fresh_profile'],
                        'warnings' => [],
                    ],
                ],
                'excluded_candidates' => [
                    [
                        'vendor_id' => 'vendor-2',
                        'vendor_name' => 'Beta Supply',
                        'reason' => 'Vendor status is suspended; only approved vendors are eligible.',
                    ],
                ],
                'provider_explanation' => 'Ranked first by the provider.',
                'deterministic_reason_set' => ['Category overlap: facilities.'],
                'provenance' => [
                    'provider_name' => 'openrouter',
                    'endpoint_group' => 'sourcing_recommendation',
                    'model_revision' => 'openai/gpt-4.1-mini:2026-04-01',
                    'prompt_template_version' => 'vendor-ranking@2026-04-24',
                    'request_trace_id' => 'trace-vendor-123',
                    'input_hash' => 'sha256:input',
                    'output_hash' => 'sha256:output',
                    'latency_ms' => 653,
                    'confidence' => 0.91,
                    'reliability_hints' => ['provider_confidence' => 'high'],
                    'processed_at' => '2026-04-24T09:30:00+08:00',
                ],
                'unavailable_reason' => null,
            ],
            $result->toArray(),
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
            processedAt: new DateTimeImmutable('2026-04-24T09:30:00+08:00'),
        );
    }
}
