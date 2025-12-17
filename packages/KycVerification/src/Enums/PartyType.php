<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Enums;

/**
 * Party type classification for KYC purposes.
 */
enum PartyType: string
{
    /**
     * Individual/Natural person
     */
    case INDIVIDUAL = 'individual';

    /**
     * Corporate entity/Legal person
     */
    case CORPORATE = 'corporate';

    /**
     * Sole proprietorship
     */
    case SOLE_PROPRIETORSHIP = 'sole_proprietorship';

    /**
     * Partnership
     */
    case PARTNERSHIP = 'partnership';

    /**
     * Trust
     */
    case TRUST = 'trust';

    /**
     * Foundation
     */
    case FOUNDATION = 'foundation';

    /**
     * Government entity
     */
    case GOVERNMENT = 'government';

    /**
     * Non-profit organization
     */
    case NON_PROFIT = 'non_profit';

    /**
     * Check if this party type requires UBO tracking
     */
    public function requiresUboTracking(): bool
    {
        return in_array($this, [
            self::CORPORATE,
            self::PARTNERSHIP,
            self::TRUST,
            self::FOUNDATION,
        ], true);
    }

    /**
     * Check if this is a legal entity (vs natural person)
     */
    public function isLegalEntity(): bool
    {
        return $this !== self::INDIVIDUAL;
    }

    /**
     * Get required document categories for this party type
     * 
     * @return array<string>
     */
    public function requiredDocumentCategories(): array
    {
        return match ($this) {
            self::INDIVIDUAL => ['identity', 'address'],
            self::CORPORATE, self::PARTNERSHIP => ['corporate', 'identity', 'address', 'ubo'],
            self::SOLE_PROPRIETORSHIP => ['identity', 'address', 'business'],
            self::TRUST => ['trust', 'identity', 'ubo'],
            self::FOUNDATION => ['foundation', 'identity', 'ubo'],
            self::GOVERNMENT => ['identity', 'authorization'],
            self::NON_PROFIT => ['corporate', 'identity', 'address'],
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'Individual',
            self::CORPORATE => 'Corporation',
            self::SOLE_PROPRIETORSHIP => 'Sole Proprietorship',
            self::PARTNERSHIP => 'Partnership',
            self::TRUST => 'Trust',
            self::FOUNDATION => 'Foundation',
            self::GOVERNMENT => 'Government Entity',
            self::NON_PROFIT => 'Non-Profit Organization',
        };
    }
}
