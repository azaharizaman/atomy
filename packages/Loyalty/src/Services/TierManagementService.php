<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Services;

use DateTimeImmutable;
use Nexus\Loyalty\Contracts\TierManagerInterface;
use Nexus\Loyalty\Entities\LoyaltyProfile;
use Nexus\Loyalty\ValueObjects\TierStatus;

/**
 * Service for evaluating member tier status and managing progression/retention.
 * Requirements: FUN-LOY-002, FUN-LOY-006, FUN-LOY-201, FUN-LOY-202, FUN-LOY-203
 */
final readonly class TierManagementService implements TierManagerInterface
{
    /**
     * @param array<string, array{threshold: int, name: string, benefits: array<string>}> $tierConfig Configuration for each tier level.
     * @param int $defaultRetentionMonths Number of months a member retains a tier.
     * @param int $evaluationWindowDays Sliding window for qualifying points (e.g., 365).
     */
    public function __construct(
        private array $tierConfig = [
            'bronze' => ['threshold' => 0, 'name' => 'Bronze Status', 'benefits' => ['standard_support']],
            'gold' => ['threshold' => 5000, 'name' => 'Gold Status', 'benefits' => ['standard_support', 'free_shipping']],
            'platinum' => ['threshold' => 15000, 'name' => 'Platinum Status', 'benefits' => ['priority_support', 'free_shipping', 'exclusive_events']],
        ],
        private int $defaultRetentionMonths = 12,
        private int $evaluationWindowDays = 365
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateTierProgression(LoyaltyProfile $profile): TierStatus
    {
        // Use lifetime or qualifying points based on config (FUN-LOY-201)
        $qualifyingPoints = $profile->balance->lifetimeEarned;

        return $this->evaluateNewStatus($profile, $qualifyingPoints);
    }

    /**
     * {@inheritdoc}
     */
    public function recalculateTierStatus(LoyaltyProfile $profile, int $lookBackDays): TierStatus
    {
        // In a real system, we would query the historical ledger for points earned in $lookBackDays
        $qualifyingPoints = $profile->metadata['qualifying_points_window'] ?? 0;

        return $this->evaluateNewStatus($profile, (int) $qualifyingPoints);
    }

    /**
     * Internal logic for evaluating status against thresholds.
     */
    private function evaluateNewStatus(LoyaltyProfile $profile, int $points): TierStatus
    {
        // Explicitly choose the tier with the highest threshold <= points (FUN-LOY-002)
        $bestTier = 'bronze';
        
        // Sort config by threshold descending to find the highest qualifying tier first
        $sortedTiers = $this->tierConfig;
        uasort($sortedTiers, fn($a, $b) => $b['threshold'] <=> $a['threshold']);

        foreach ($sortedTiers as $tierId => $config) {
            if ($points >= $config['threshold']) {
                $bestTier = $tierId;
                break;
            }
        }

        // Tier Maintenance Logic: Promotion grants retention period (FUN-LOY-203)
        // If they already have a higher tier and it's not expired, keep it.
        $now = new DateTimeImmutable();
        if ($profile->tier->isValid($now) && $this->isHigherTier($profile->tier->tierId, $bestTier)) {
            return $profile->tier;
        }

        $config = $this->tierConfig[$bestTier];
        $expiry = $now->modify(sprintf("+%d months", $this->defaultRetentionMonths));

        return new TierStatus(
            $bestTier,
            (string) $config['name'],
            $now,
            $expiry,
            (array) $config['benefits']
        );
    }

    /**
     * Helper to determine if tier A is higher than tier B.
     */
    private function isHigherTier(string $tierA, string $tierB): bool
    {
        $tiers = array_keys($this->tierConfig);
        $posA = array_search($tierA, $tiers);
        $posB = array_search($tierB, $tiers);

        // Treat unknown tiers as lowest (position -1)
        $valA = ($posA === false) ? -1 : $posA;
        $valB = ($posB === false) ? -1 : $posB;

        return $valA > $valB;
    }
}
