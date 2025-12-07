<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Common\ValueObjects\Money;

/**
 * Handles vendor selection and scoring logic.
 *
 * This service is responsible for:
 * - Evaluating vendor performance scores
 * - Selecting optimal vendors based on criteria
 * - Calculating vendor scores from multiple factors
 */
final readonly class VendorSelectionService
{
    /**
     * Calculate vendor score based on multiple factors.
     *
     * @param array{
     *     price_competitiveness: float,
     *     delivery_reliability: float,
     *     quality_rating: float,
     *     payment_terms_score: float,
     *     relationship_score: float
     * } $factors Score factors (0.0 to 1.0 scale)
     * @param array<string, float> $weights Custom weights for each factor
     * @return float Weighted score (0.0 to 100.0)
     */
    public function calculateVendorScore(
        array $factors,
        array $weights = []
    ): float {
        $defaultWeights = [
            'price_competitiveness' => 0.30,
            'delivery_reliability' => 0.25,
            'quality_rating' => 0.25,
            'payment_terms_score' => 0.10,
            'relationship_score' => 0.10,
        ];

        $weights = array_merge($defaultWeights, $weights);

        $totalScore = 0.0;
        $totalWeight = 0.0;

        foreach ($factors as $factor => $value) {
            if (isset($weights[$factor])) {
                $totalScore += $value * $weights[$factor];
                $totalWeight += $weights[$factor];
            }
        }

        if ($totalWeight === 0.0) {
            return 0.0;
        }

        // Normalize to 100-point scale
        return ($totalScore / $totalWeight) * 100;
    }

    /**
     * Select best vendor from candidates based on scores and criteria.
     *
     * @param array<array{
     *     vendor_id: string,
     *     score: float,
     *     unit_price: Money,
     *     lead_time_days: int,
     *     meets_requirements: bool
     * }> $candidates List of vendor candidates
     * @param array{
     *     minimum_score?: float,
     *     maximum_lead_time_days?: int,
     *     prefer_lowest_price?: bool
     * } $criteria Selection criteria
     * @return string|null Selected vendor ID or null if none qualify
     */
    public function selectBestVendor(
        array $candidates,
        array $criteria = []
    ): ?string {
        $minimumScore = $criteria['minimum_score'] ?? 60.0;
        $maxLeadTime = $criteria['maximum_lead_time_days'] ?? null;
        $preferLowestPrice = $criteria['prefer_lowest_price'] ?? false;

        // Filter candidates that meet requirements
        $qualifiedCandidates = array_filter(
            $candidates,
            function (array $candidate) use ($minimumScore, $maxLeadTime): bool {
                if (!$candidate['meets_requirements']) {
                    return false;
                }

                if ($candidate['score'] < $minimumScore) {
                    return false;
                }

                if ($maxLeadTime !== null && $candidate['lead_time_days'] > $maxLeadTime) {
                    return false;
                }

                return true;
            }
        );

        if (empty($qualifiedCandidates)) {
            return null;
        }

        // Sort by preference
        if ($preferLowestPrice) {
            usort($qualifiedCandidates, function (array $a, array $b): int {
                $priceCompare = $a['unit_price']->getAmount() <=> $b['unit_price']->getAmount();
                if ($priceCompare !== 0) {
                    return $priceCompare;
                }
                // If same price, prefer higher score
                return $b['score'] <=> $a['score'];
            });
        } else {
            // Sort by score (highest first)
            usort($qualifiedCandidates, fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        }

        return $qualifiedCandidates[0]['vendor_id'];
    }

    /**
     * Rank vendors by overall value proposition.
     *
     * @param array<array{
     *     vendor_id: string,
     *     score: float,
     *     unit_price: Money,
     *     lead_time_days: int
     * }> $vendors List of vendors to rank
     * @return array<array{
     *     vendor_id: string,
     *     rank: int,
     *     value_score: float
     * }> Ranked vendors with value scores
     */
    public function rankVendorsByValue(array $vendors): array
    {
        if (empty($vendors)) {
            return [];
        }

        // Calculate normalized scores
        $maxScore = max(array_column($vendors, 'score'));
        $minPrice = min(array_map(fn ($v) => $v['unit_price']->getAmount(), $vendors));
        $maxPrice = max(array_map(fn ($v) => $v['unit_price']->getAmount(), $vendors));
        $minLeadTime = min(array_column($vendors, 'lead_time_days'));
        $maxLeadTime = max(array_column($vendors, 'lead_time_days'));

        $priceRange = $maxPrice - $minPrice;
        $leadTimeRange = $maxLeadTime - $minLeadTime;

        $rankedVendors = array_map(function (array $vendor) use (
            $maxScore,
            $minPrice,
            $priceRange,
            $minLeadTime,
            $leadTimeRange
        ): array {
            // Normalize score (0-1, higher is better)
            $normalizedScore = $maxScore > 0 ? $vendor['score'] / $maxScore : 0;

            // Normalize price (0-1, lower price = higher score)
            $normalizedPrice = $priceRange > 0
                ? 1 - (($vendor['unit_price']->getAmount() - $minPrice) / $priceRange)
                : 1;

            // Normalize lead time (0-1, lower lead time = higher score)
            $normalizedLeadTime = $leadTimeRange > 0
                ? 1 - (($vendor['lead_time_days'] - $minLeadTime) / $leadTimeRange)
                : 1;

            // Calculate composite value score
            $valueScore = ($normalizedScore * 0.4) + ($normalizedPrice * 0.4) + ($normalizedLeadTime * 0.2);

            return [
                'vendor_id' => $vendor['vendor_id'],
                'value_score' => round($valueScore * 100, 2),
                'rank' => 0, // Will be set after sorting
            ];
        }, $vendors);

        // Sort by value score (highest first)
        usort($rankedVendors, fn (array $a, array $b): int => $b['value_score'] <=> $a['value_score']);

        // Assign ranks
        foreach ($rankedVendors as $index => $vendor) {
            $rankedVendors[$index]['rank'] = $index + 1;
        }

        return $rankedVendors;
    }

    /**
     * Check if vendor meets minimum qualification criteria.
     *
     * @param array{
     *     is_active: bool,
     *     is_approved: bool,
     *     performance_score: float,
     *     credit_status: string,
     *     certifications: array<string>
     * } $vendorProfile Vendor profile data
     * @param array{
     *     required_certifications?: array<string>,
     *     minimum_performance_score?: float,
     *     allowed_credit_statuses?: array<string>
     * } $requirements Qualification requirements
     * @return array{
     *     qualifies: bool,
     *     reasons: array<string>
     * } Qualification result with reasons
     */
    public function checkVendorQualification(
        array $vendorProfile,
        array $requirements = []
    ): array {
        $reasons = [];

        // Check active status
        if (!$vendorProfile['is_active']) {
            $reasons[] = 'Vendor is not active';
        }

        // Check approval status
        if (!$vendorProfile['is_approved']) {
            $reasons[] = 'Vendor is not approved';
        }

        // Check credit status
        $allowedStatuses = $requirements['allowed_credit_statuses'] ?? ['good', 'excellent'];
        if (!in_array($vendorProfile['credit_status'], $allowedStatuses, true)) {
            $reasons[] = sprintf(
                'Credit status "%s" not in allowed statuses: %s',
                $vendorProfile['credit_status'],
                implode(', ', $allowedStatuses)
            );
        }

        // Check minimum performance score
        $minScore = $requirements['minimum_performance_score'] ?? 50.0;
        if ($vendorProfile['performance_score'] < $minScore) {
            $reasons[] = sprintf(
                'Performance score %.1f is below minimum %.1f',
                $vendorProfile['performance_score'],
                $minScore
            );
        }

        // Check required certifications
        $requiredCerts = $requirements['required_certifications'] ?? [];
        $missingCerts = array_diff($requiredCerts, $vendorProfile['certifications']);
        if (!empty($missingCerts)) {
            $reasons[] = sprintf(
                'Missing required certifications: %s',
                implode(', ', $missingCerts)
            );
        }

        return [
            'qualifies' => empty($reasons),
            'reasons' => $reasons,
        ];
    }
}
