<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use DateTimeImmutable;
use Nexus\Common\Contracts\ClockInterface;
use Nexus\ProcurementOperations\Contracts\VendorScorerInterface;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationCandidate;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationResult;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationScoredCandidate;

final readonly class DeterministicVendorScorer implements VendorScorerInterface
{
    public function __construct(
        private ClockInterface $clock,
    ) {
    }

    public function score(VendorRecommendationRequest $request): VendorRecommendationResult
    {
        $scored = [];
        $excluded = [];

        foreach ($request->candidates as $candidate) {
            if ($this->normalize($candidate->status) !== 'approved') {
                $excluded[] = [
                    'vendor_id' => $candidate->vendorId,
                    'vendor_name' => $candidate->vendorName,
                    'reason' => sprintf(
                        'Vendor status is %s; only approved vendors are eligible.',
                        $this->normalize($candidate->status),
                    ),
                ];
                continue;
            }

            $scored[] = $this->scoreCandidate($request, $candidate);
        }

        usort(
            $scored,
            static fn (VendorRecommendationScoredCandidate $a, VendorRecommendationScoredCandidate $b): int =>
                $b->fitScore <=> $a->fitScore ?: strcmp($a->vendorName, $b->vendorName),
        );

        return new VendorRecommendationResult($request->tenantId, $request->rfqId, $scored, $excluded);
    }

    private function scoreCandidate(
        VendorRecommendationRequest $request,
        VendorRecommendationCandidate $candidate,
    ): VendorRecommendationScoredCandidate {
        $score = 40;
        $reasons = [];
        $warnings = [];
        $warningFlags = [];

        $categoryOverlap = $this->overlap($request->categories, $candidate->categories);
        if ($categoryOverlap !== []) {
            $score += min(25, count($categoryOverlap) * 15);
            $reasons[] = 'Category overlap: ' . implode(', ', $categoryOverlap) . '.';
        } elseif ($request->categories !== []) {
            $score -= 10;
            $warnings[] = 'No maintained category overlap with requisition.';
            $warningFlags[] = 'weak_category_match';
        }

        $narrativeTokens = $this->tokens(array_merge([$request->description], $request->lineItemSummary));
        $capabilityOverlap = array_values(array_intersect($narrativeTokens, $this->tokens($candidate->capabilities)));
        if ($capabilityOverlap !== []) {
            $score += min(15, count($capabilityOverlap) * 5);
            $reasons[] = 'Capability evidence: ' . implode(', ', array_slice($capabilityOverlap, 0, 3)) . '.';
        }

        $requestedGeography = $request->geography !== null ? strtoupper(trim($request->geography)) : null;
        $candidateRegions = array_map(static fn (string $region): string => strtoupper(trim($region)), $candidate->regions);
        $candidateRegions = array_values(array_filter($candidateRegions, static fn (string $region): bool => $region !== ''));
        if ($requestedGeography !== null && $requestedGeography !== '') {
            if (in_array($requestedGeography, $candidateRegions, true)) {
                $score += 10;
                $reasons[] = 'Geography coverage matches ' . $requestedGeography . '.';
            } elseif ($candidateRegions === []) {
                $warningFlags[] = 'unknown_geography';
                $warnings[] = 'Vendor has not specified regional coverage.';
            } else {
                $score -= 20;
                $warningFlags[] = 'weak_geography_match';
                $warnings[] = sprintf(
                    'Geography mismatch: requested %s, vendor covers %s.',
                    $requestedGeography,
                    implode(', ', $candidateRegions),
                );
            }
        }

        if ($candidate->spendBand !== null && $request->spendBand !== null) {
            if ($this->normalize($candidate->spendBand) === $this->normalize($request->spendBand)) {
                $score += 5;
                $reasons[] = 'Spend band fit: ' . $this->normalize($request->spendBand) . '.';
            } else {
                $score -= 5;
                $warnings[] = 'Spend band differs from requisition estimate.';
            }
        }

        if ($candidate->historicalParticipationCount > 0) {
            $score += min(10, $candidate->historicalParticipationCount * 2);
            $reasons[] = 'Historical participation signal present.';
        }

        if ($candidate->historicalAwardCount > 0) {
            $score += min(10, $candidate->historicalAwardCount * 3);
            $reasons[] = 'Historical award signal present.';
        }

        if ($candidate->lastActiveAt !== null) {
            $daysSinceActive = $this->daysSince($candidate->lastActiveAt);
            if ($daysSinceActive <= 90) {
                $score += 10;
                $reasons[] = 'Recent activity within 90 days.';
            } elseif ($daysSinceActive > 365) {
                $score -= 8;
                $warningFlags[] = 'sparse_historical_signal';
                $warnings[] = 'No recent activity in the last year.';
            }
        } else {
            $warningFlags[] = 'sparse_historical_signal';
            $warnings[] = 'No recent activity signal available.';
        }

        if ($candidate->preferred) {
            $score += 5;
            $reasons[] = 'Preferred vendor signal present.';
        }

        $score = max(0, min(100, $score));
        if ($reasons === []) {
            $reasons[] = 'Approved vendor with limited deterministic fit evidence.';
        }

        return new VendorRecommendationScoredCandidate(
            vendorId: $candidate->vendorId,
            vendorName: $candidate->vendorName,
            fitScore: $score,
            confidenceBand: VendorRecommendationScoredCandidate::confidenceBandFor($score),
            recommendedReasonSummary: $reasons[0],
            deterministicReasons: $reasons,
            warningFlags: array_values(array_unique($warningFlags)),
            warnings: array_values(array_unique($warnings)),
        );
    }

    /**
     * @param list<string> $left
     * @param list<string> $right
     *
     * @return list<string>
     */
    private function overlap(array $left, array $right): array
    {
        return array_values(array_intersect(
            array_map(fn (string $value): string => $this->normalize($value), $left),
            array_map(fn (string $value): string => $this->normalize($value), $right),
        ));
    }

    /**
     * @param list<string> $values
     *
     * @return list<string>
     */
    private function tokens(array $values): array
    {
        $tokens = [];
        foreach ($values as $value) {
            foreach (preg_split('/[^a-z0-9]+/', $this->normalize($value)) ?: [] as $token) {
                if (strlen($token) >= 3) {
                    $tokens[] = $token;
                }
            }
        }

        return array_values(array_unique($tokens));
    }

    private function normalize(string $value): string
    {
        return strtolower(trim($value));
    }

    private function daysSince(DateTimeImmutable $date): int
    {
        return (int) $date->diff($this->clock->now())->format('%a');
    }
}
