<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\ProcurementOperations\DTOs\QuoteSubmission;

/**
 * Service for comparing vendor quotes based on multiple weighted criteria.
 */
final readonly class QuoteComparisonService
{
    /**
     * Compare submissions and return a ranked list.
     *
     * @param array<string, QuoteSubmission> $submissions Submissions indexed by vendor ID
     * @param array{price: float, quality: float, delivery: float} $weights
     * @return array<int, array{vendorId: string, totalAmountCents: int, score: float, rank: int}>
     */
    public function compare(array $submissions, array $weights): array
    {
        $rankings = [];
        
        // Find min price for normalization
        $minPrice = PHP_INT_MAX;
        foreach ($submissions as $submission) {
            $total = $this->calculateTotal($submission);
            if ($total < $minPrice) {
                $minPrice = $total;
            }
        }

        foreach ($submissions as $vendorId => $submission) {
             $totalAmount = $this->calculateTotal($submission);
             
             // Scoring logic:
             // Price Score (0-100): Lower is better. Normalizing against min price.
             $priceScore = ($totalAmount > 0) ? ($minPrice / $totalAmount) * 100 : 0;
             
             // Quality and Delivery scores are placeholders for actual vendor performance metrics
             $qualityScore = 80; // Placeholder
             $deliveryScore = 90; // Placeholder

             $finalScore = ($priceScore * ($weights['price'] ?? 0.5)) 
                         + ($qualityScore * ($weights['quality'] ?? 0.3))
                         + ($deliveryScore * ($weights['delivery'] ?? 0.2));
             
             $rankings[] = [
                 'vendorId' => $vendorId,
                 'totalAmountCents' => (int)$totalAmount,
                 'score' => (float)$finalScore,
             ];
        }

        // Sort by score descending
        usort($rankings, fn($a, $b) => $b['score'] <=> $a['score']);
        
        foreach ($rankings as $index => &$ranking) {
            $ranking['rank'] = $index + 1;
        }

        return $rankings;
    }

    /**
     * Calculate total price of a submission.
     */
    private function calculateTotal(QuoteSubmission $submission): int
    {
        return array_reduce(
            $submission->items,
            fn(int $carry, array $item) => $carry + ($item['unitPriceCents'] ?? 0),
            0
        );
    }
}
