<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Approval levels for procurement documents.
 *
 * Defines the hierarchy of approval authority based on
 * amount thresholds and organizational structure.
 *
 * Default Thresholds:
 * - Level 1: < $5,000 → Direct Manager
 * - Level 2: $5,000 - $25,000 → Department Head
 * - Level 3: > $25,000 → Finance Director
 */
enum ApprovalLevel: int
{
    case LEVEL_1 = 1;
    case LEVEL_2 = 2;
    case LEVEL_3 = 3;
    case LEVEL_4 = 4; // Reserved for future use (e.g., CFO)
    case LEVEL_5 = 5; // Reserved for future use (e.g., CEO/Board)

    /**
     * Get the human-readable label for this level.
     */
    public function label(): string
    {
        return match ($this) {
            self::LEVEL_1 => 'Direct Manager',
            self::LEVEL_2 => 'Department Head',
            self::LEVEL_3 => 'Finance Director',
            self::LEVEL_4 => 'Chief Financial Officer',
            self::LEVEL_5 => 'Executive Approval',
        };
    }

    /**
     * Get the default threshold in cents for this level.
     *
     * These are defaults that can be overridden via settings.
     */
    public function defaultThresholdCents(): int
    {
        return match ($this) {
            self::LEVEL_1 => 500000,     // $5,000
            self::LEVEL_2 => 2500000,    // $25,000
            self::LEVEL_3 => 10000000,   // $100,000
            self::LEVEL_4 => 50000000,   // $500,000
            self::LEVEL_5 => PHP_INT_MAX, // Unlimited
        };
    }

    /**
     * Get the setting key for this level's threshold.
     */
    public function settingKey(): string
    {
        return sprintf('procurement.approval.threshold_level_%d_cents', $this->value);
    }

    /**
     * Get the next higher approval level.
     */
    public function nextLevel(): ?self
    {
        return match ($this) {
            self::LEVEL_1 => self::LEVEL_2,
            self::LEVEL_2 => self::LEVEL_3,
            self::LEVEL_3 => self::LEVEL_4,
            self::LEVEL_4 => self::LEVEL_5,
            self::LEVEL_5 => null,
        };
    }

    /**
     * Check if this level is sufficient for the given amount.
     */
    public function isSufficientFor(int $amountCents, array $configuredThresholds = []): bool
    {
        $threshold = $configuredThresholds[$this->value] ?? $this->defaultThresholdCents();
        return $amountCents <= $threshold;
    }

    /**
     * Determine the required approval level for an amount.
     *
     * @param int $amountCents Amount in cents
     * @param array<int, int> $configuredThresholds Optional configured thresholds
     */
    public static function forAmount(int $amountCents, array $configuredThresholds = []): self
    {
        foreach (self::cases() as $level) {
            if ($level->isSufficientFor($amountCents, $configuredThresholds)) {
                return $level;
            }
        }

        return self::LEVEL_5;
    }
}
