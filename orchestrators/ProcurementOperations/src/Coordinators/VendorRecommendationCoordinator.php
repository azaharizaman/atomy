<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use DateTimeImmutable;
use Nexus\MachineLearning\Enums\AiEndpointGroup;
use Nexus\ProcurementML\Enums\VendorRecommendationResultStatus;
use Nexus\ProcurementML\ValueObjects\ProviderAiProvenance;
use Nexus\ProcurementML\ValueObjects\VendorRecommendationEligibleCandidate;
use Nexus\ProcurementML\ValueObjects\VendorRecommendationExcludedCandidate;
use Nexus\ProcurementML\ValueObjects\VendorRecommendationResult;
use Nexus\ProcurementOperations\Contracts\VendorRecommendationCoordinatorInterface;
use Nexus\ProcurementOperations\Contracts\VendorRecommendationLlmInterface;
use Nexus\ProcurementOperations\Contracts\VendorScorerInterface;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationCandidate;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationResult as DeterministicVendorRecommendationResult;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationScoredCandidate;
use Nexus\ProcurementOperations\Exceptions\InvalidVendorRecommendation;
use Throwable;

final readonly class VendorRecommendationCoordinator implements VendorRecommendationCoordinatorInterface
{
    public const int MAX_LLM_SCORE_DELTA = 10;

    public function __construct(
        private VendorScorerInterface $scorer,
        private VendorRecommendationLlmInterface $llm,
    ) {
    }

    public function recommend(VendorRecommendationRequest $request): VendorRecommendationResult
    {
        $deterministic = $this->scorer->score($request);
        $eligibleByVendorId = $this->eligibleCandidatesByVendorId($deterministic);

        try {
            $providerPayload = $this->llm->enrich($request, $deterministic->candidates);
        } catch (Throwable) {
            return $this->unavailableResult($request, 'provider_unavailable');
        }

        $parsed = $this->parseProviderPayload($providerPayload, $eligibleByVendorId);
        if ($parsed === null) {
            return $this->unavailableResult($request, 'provider_output_rejected');
        }

        [$providerExplanation, $providerCandidates, $provenance] = $parsed;

        $eligibleCandidates = [];
        foreach ($providerCandidates as $providerCandidate) {
            $vendorId = trim((string) ($providerCandidate['vendor_id'] ?? ''));
            $deterministicCandidate = $eligibleByVendorId[$vendorId];

            $fitScore = $deterministicCandidate->fitScore;
            if (array_key_exists('score_delta', $providerCandidate)) {
                $delta = $this->boundedDelta($providerCandidate['score_delta']);
                if ($delta === null) {
                    return $this->unavailableResult($request, 'provider_output_rejected');
                }

                $fitScore = max(0, min(100, $fitScore + $delta));
            }

            $eligibleCandidates[] = new VendorRecommendationEligibleCandidate(
                vendorId: $deterministicCandidate->vendorId,
                vendorName: $deterministicCandidate->vendorName,
                fitScore: $fitScore,
                confidenceBand: VendorRecommendationScoredCandidate::confidenceBandFor($fitScore),
                providerExplanation: $this->providerCandidateExplanation(
                    $providerCandidate,
                    $providerExplanation,
                    $deterministicCandidate->recommendedReasonSummary,
                ),
                deterministicReasons: $this->stringList($deterministicCandidate->deterministicReasons),
                llmInsights: $this->stringList($providerCandidate['llm_insights'] ?? []),
                warningFlags: $this->stringList($deterministicCandidate->warningFlags),
                warnings: $this->stringList($deterministicCandidate->warnings),
            );
        }

        $excludedCandidates = [];
        foreach ($deterministic->excludedReasons as $excludedReason) {
            $excludedCandidates[] = new VendorRecommendationExcludedCandidate(
                vendorId: (string) $excludedReason['vendor_id'],
                vendorName: (string) $excludedReason['vendor_name'],
                reason: (string) $excludedReason['reason'],
            );
        }

        return VendorRecommendationResult::available(
            tenantId: $request->tenantId,
            rfqId: $request->rfqId,
            eligibleCandidates: $eligibleCandidates,
            excludedCandidates: $excludedCandidates,
            providerExplanation: $providerExplanation,
            deterministicReasonSet: $this->deterministicReasonSet($deterministic),
            provenance: $provenance,
        );
    }

    /**
     * @param array<string, VendorRecommendationScoredCandidate> $eligibleByVendorId
     * @return array{0: ?string, 1: list<array<string, mixed>>, 2: ProviderAiProvenance}|null
     */
    private function parseProviderPayload(array $payload, array $eligibleByVendorId): ?array
    {
        $eligibleCandidates = $payload['eligible_candidates'] ?? null;
        if (!is_array($eligibleCandidates) || !array_is_list($eligibleCandidates)) {
            return null;
        }

        $providerExplanation = $this->nullableString($payload['provider_explanation'] ?? null);
        $provenance = $this->parseProvenance($payload['provenance'] ?? null);
        if ($provenance === null) {
            return null;
        }

        $seenVendorIds = [];
        $validatedCandidates = [];
        foreach ($eligibleCandidates as $candidate) {
            if (!is_array($candidate)) {
                return null;
            }

            $vendorId = $this->nullableString($candidate['vendor_id'] ?? null);
            $vendorName = $this->nullableString($candidate['vendor_name'] ?? null);
            if ($vendorId === null || $vendorName === null) {
                return null;
            }

            if (isset($seenVendorIds[$vendorId])) {
                return null;
            }

            if (!isset($eligibleByVendorId[$vendorId])) {
                return null;
            }

            if (strcasecmp($eligibleByVendorId[$vendorId]->vendorName, $vendorName) !== 0) {
                return null;
            }

            $seenVendorIds[$vendorId] = true;
            $validatedCandidates[] = $candidate;
        }

        return [$providerExplanation, $validatedCandidates, $provenance];
    }

    /**
     * @param array<string, VendorRecommendationScoredCandidate> $eligibleByVendorId
     * @return array<string, VendorRecommendationScoredCandidate>
     */
    private function eligibleCandidatesByVendorId(DeterministicVendorRecommendationResult $deterministic): array
    {
        $eligibleByVendorId = [];
        foreach ($deterministic->candidates as $candidate) {
            $eligibleByVendorId[$candidate->vendorId] = $candidate;
        }

        return $eligibleByVendorId;
    }

    private function unavailableResult(VendorRecommendationRequest $request, string $reason): VendorRecommendationResult
    {
        return VendorRecommendationResult::unavailable(
            tenantId: $request->tenantId,
            rfqId: $request->rfqId,
            reason: $reason,
        );
    }

    private function parseProvenance(mixed $provenance): ?ProviderAiProvenance
    {
        if (!is_array($provenance)) {
            return null;
        }

        $providerName = $this->nullableString($provenance['provider_name'] ?? null);
        $endpointGroup = $this->nullableString($provenance['endpoint_group'] ?? null);
        $modelRevision = $this->nullableString($provenance['model_revision'] ?? null);
        $promptTemplateVersion = $this->nullableString($provenance['prompt_template_version'] ?? null);
        $requestTraceId = $this->nullableString($provenance['request_trace_id'] ?? null);
        $inputHash = $this->nullableString($provenance['input_hash'] ?? null);
        $outputHash = $this->nullableString($provenance['output_hash'] ?? null);
        $latencyMs = $this->boundedNonNegativeInt($provenance['latency_ms'] ?? null);
        $processedAt = $this->parseDateTime($provenance['processed_at'] ?? null);

        if (
            $providerName === null
            || $endpointGroup === null
            || $modelRevision === null
            || $promptTemplateVersion === null
            || $requestTraceId === null
            || $inputHash === null
            || $outputHash === null
            || $latencyMs === null
            || $processedAt === null
        ) {
            return null;
        }

        try {
            return new ProviderAiProvenance(
                providerName: $providerName,
                endpointGroup: AiEndpointGroup::fromConfig($endpointGroup),
                modelRevision: $modelRevision,
                promptTemplateVersion: $promptTemplateVersion,
                requestTraceId: $requestTraceId,
                inputHash: $inputHash,
                outputHash: $outputHash,
                latencyMs: $latencyMs,
                confidence: $this->nullableFloat($provenance['confidence'] ?? null),
                reliabilityHints: $this->scalarMap($provenance['reliability_hints'] ?? []),
                processedAt: $processedAt,
            );
        } catch (InvalidVendorRecommendation) {
            return null;
        } catch (Throwable) {
            return null;
        }
    }

    private function providerCandidateExplanation(array $providerCandidate, ?string $providerExplanation, string $fallback): string
    {
        $candidateExplanation = $this->nullableString($providerCandidate['provider_explanation'] ?? null);
        if ($candidateExplanation !== null) {
            return $candidateExplanation;
        }

        $candidateExplanation = $this->nullableString($providerCandidate['reason_summary'] ?? null);
        if ($candidateExplanation !== null) {
            return $candidateExplanation;
        }

        return $providerExplanation ?? $fallback;
    }

    private function boundedDelta(mixed $value): ?int
    {
        if (!is_int($value) && !is_string($value)) {
            return null;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) !== 1) {
            return null;
        }

        $delta = (int) $value;

        return max(-self::MAX_LLM_SCORE_DELTA, min(self::MAX_LLM_SCORE_DELTA, $delta));
    }

    private function boundedNonNegativeInt(mixed $value): ?int
    {
        if (!is_int($value) && !is_string($value)) {
            return null;
        }

        if (is_string($value) && preg_match('/^\d+$/', trim($value)) !== 1) {
            return null;
        }

        $value = (int) $value;

        return $value < 0 ? null : $value;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @param array<string, mixed> $value
     * @return array<string, scalar|null>
     */
    private function scalarMap(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            if (!is_string($key) || trim($key) === '') {
                continue;
            }

            if (!is_scalar($item) && $item !== null) {
                continue;
            }

            $normalized[trim($key)] = $item;
        }

        return $normalized;
    }

    /**
     * @param list<string> $values
     * @return list<string>
     */
    private function stringList(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $normalized = [];
        foreach ($values as $value) {
            if (!is_string($value)) {
                continue;
            }

            $value = trim($value);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @return list<string>
     */
    private function deterministicReasonSet(DeterministicVendorRecommendationResult $deterministic): array
    {
        $reasons = [];

        foreach ($deterministic->candidates as $candidate) {
            foreach ($candidate->deterministicReasons as $reason) {
                $reason = trim((string) $reason);
                if ($reason !== '') {
                    $reasons[] = $reason;
                }
            }
        }

        foreach ($deterministic->excludedReasons as $excludedReason) {
            $reason = trim((string) ($excludedReason['reason'] ?? ''));
            if ($reason !== '') {
                $reasons[] = $reason;
            }
        }

        return array_values(array_unique($reasons));
    }

    private function parseDateTime(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value)) {
            return null;
        }

        try {
            return new DateTimeImmutable(trim($value));
        } catch (Throwable) {
            return null;
        }
    }
}
