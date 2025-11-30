<?php

declare(strict_types=1);

namespace Nexus\Sequencing\ValueObjects;

use Nexus\Sequencing\Exceptions\InvalidGapPolicyException;

/**
 * Value object representing how to handle gaps in sequences.
 */
enum GapPolicy: string
{
    case ALLOW_GAPS = 'allow_gaps';
    case FILL_GAPS = 'fill_gaps';
    case REPORT_GAPS_ONLY = 'report_gaps_only';

    /**
     * Create from string value.
     *
     * @throws InvalidGapPolicyException
     */
    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'allow_gaps' => self::ALLOW_GAPS,
            'fill_gaps' => self::FILL_GAPS,
            'report_gaps_only' => self::REPORT_GAPS_ONLY,
            default => throw InvalidGapPolicyException::unknownPolicy($value),
        };
    }

    /**
     * Check if this policy allows filling gaps.
     */
    public function allowsFilling(): bool
    {
        return $this === self::FILL_GAPS;
    }

    /**
     * Check if this policy requires tracking gaps.
     */
    public function requiresTracking(): bool
    {
        return $this !== self::ALLOW_GAPS;
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ALLOW_GAPS => 'Allow Gaps',
            self::FILL_GAPS => 'Fill Gaps',
            self::REPORT_GAPS_ONLY => 'Report Gaps Only',
        };
    }

    /**
     * Get description of the policy.
     */
    public function description(): string
    {
        return match ($this) {
            self::ALLOW_GAPS => 'Gaps are allowed (default behavior when transactions fail)',
            self::FILL_GAPS => 'Reuse voided/cancelled numbers to fill gaps in the sequence',
            self::REPORT_GAPS_ONLY => 'Track gaps for reporting but do not fill them',
        };
    }
}
