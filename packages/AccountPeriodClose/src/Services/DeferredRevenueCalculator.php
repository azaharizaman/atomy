<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Services;

use Nexus\AccountPeriodClose\ValueObjects\ClosingEntrySpec;

/**
 * Pure logic for deferred revenue recognition.
 */
final readonly class DeferredRevenueCalculator
{
    /**
     * Calculate deferred revenue to recognize based on time elapsed.
     *
     * @param float $totalDeferredAmount
     * @param \DateTimeImmutable $startDate
     * @param \DateTimeImmutable $endDate
     * @param \DateTimeImmutable $periodEndDate
     * @return float Amount to recognize in current period
     */
    public function calculateRecognition(
        float $totalDeferredAmount,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        \DateTimeImmutable $periodEndDate
    ): float {
        $totalDays = $startDate->diff($endDate)->days;
        if ($totalDays <= 0) {
            return $totalDeferredAmount;
        }

        $elapsedDays = $startDate->diff($periodEndDate)->days;
        $elapsedDays = min($elapsedDays, $totalDays);

        return ($totalDeferredAmount / $totalDays) * $elapsedDays;
    }

    /**
     * Generate entries for deferred revenue recognition.
     *
     * @param array<array{deferred_account: string, revenue_account: string, total_amount: float, start_date: \DateTimeImmutable, end_date: \DateTimeImmutable}> $deferredItems
     * @param \DateTimeImmutable $periodEndDate
     * @return array<ClosingEntrySpec>
     */
    public function generateRecognitionEntries(
        array $deferredItems,
        \DateTimeImmutable $periodEndDate
    ): array {
        $entries = [];

        foreach ($deferredItems as $item) {
            $amountToRecognize = $this->calculateRecognition(
                $item['total_amount'],
                $item['start_date'],
                $item['end_date'],
                $periodEndDate
            );

            if ($amountToRecognize > 0) {
                $entries[] = new ClosingEntrySpec(
                    description: 'Recognize deferred revenue',
                    debitAccountCode: $item['deferred_account'],
                    creditAccountCode: $item['revenue_account'],
                    amount: $amountToRecognize,
                    entryDate: $periodEndDate,
                    reference: 'ADJ-DEFERRED'
                );
            }
        }

        return $entries;
    }
}
