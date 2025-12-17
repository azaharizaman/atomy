<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Enums;

/**
 * Severity levels for data breaches.
 *
 * Based on common risk assessment frameworks and regulatory guidance.
 */
enum BreachSeverity: string
{
    /**
     * Low severity - minimal risk to data subjects.
     * Example: Accidental email to wrong internal recipient.
     */
    case LOW = 'low';

    /**
     * Medium severity - limited impact on data subjects.
     * Example: Loss of encrypted device with personal data.
     */
    case MEDIUM = 'medium';

    /**
     * High severity - significant impact on data subjects.
     * Example: Unauthorized access to financial records.
     */
    case HIGH = 'high';

    /**
     * Critical severity - severe impact, immediate action required.
     * Example: Large-scale data exfiltration, identity theft risk.
     */
    case CRITICAL = 'critical';

    /**
     * Get numeric score for calculations (1-4).
     */
    public function getScore(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::CRITICAL => 4,
        };
    }

    /**
     * Check if this severity requires regulatory notification.
     * Most regulations require notification for HIGH and above.
     */
    public function requiresRegulatoryNotification(): bool
    {
        return match ($this) {
            self::HIGH, self::CRITICAL => true,
            default => false,
        };
    }

    /**
     * Check if this severity requires notifying affected individuals.
     */
    public function requiresIndividualNotification(): bool
    {
        return match ($this) {
            self::HIGH, self::CRITICAL => true,
            default => false,
        };
    }

    /**
     * Check if immediate escalation is required.
     */
    public function requiresImmediateEscalation(): bool
    {
        return $this === self::CRITICAL;
    }

    /**
     * Get recommended response time in hours.
     */
    public function getRecommendedResponseHours(): int
    {
        return match ($this) {
            self::CRITICAL => 1,
            self::HIGH => 4,
            self::MEDIUM => 24,
            self::LOW => 72,
        };
    }

    /**
     * Get human-readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::LOW => 'Low Severity',
            self::MEDIUM => 'Medium Severity',
            self::HIGH => 'High Severity',
            self::CRITICAL => 'Critical Severity',
        };
    }

    /**
     * Calculate severity based on factors.
     *
     * @param int $recordsAffected Number of records affected
     * @param bool $sensitiveData Whether sensitive/special category data is involved
     * @param bool $encryptedData Whether the data was encrypted
     * @param bool $containedQuickly Whether the breach was contained quickly
     */
    public static function calculate(
        int $recordsAffected,
        bool $sensitiveData,
        bool $encryptedData,
        bool $containedQuickly,
    ): self {
        $score = 0;

        // Records affected scoring
        if ($recordsAffected >= 10000) {
            $score += 4;
        } elseif ($recordsAffected >= 1000) {
            $score += 3;
        } elseif ($recordsAffected >= 100) {
            $score += 2;
        } else {
            $score += 1;
        }

        // Sensitive data increases severity
        if ($sensitiveData) {
            $score += 2;
        }

        // Encryption reduces severity
        if ($encryptedData) {
            $score -= 1;
        }

        // Quick containment reduces severity
        if ($containedQuickly) {
            $score -= 1;
        }

        // Clamp score to valid range
        $score = max(1, min($score, 6));

        return match (true) {
            $score >= 5 => self::CRITICAL,
            $score >= 4 => self::HIGH,
            $score >= 2 => self::MEDIUM,
            default => self::LOW,
        };
    }
}
