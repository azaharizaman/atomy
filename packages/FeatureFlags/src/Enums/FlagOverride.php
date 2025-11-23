<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Enums;

/**
 * Feature flag override states for emergency control.
 *
 * Overrides take precedence over all evaluation logic (enabled state, strategy, etc.).
 * Used for kill switches and forced enablement during testing.
 */
enum FlagOverride: string
{
    /**
     * Force the flag to always return true.
     *
     * Use case: Testing, forced enablement during deployment.
     * Precedence: Second highest (FORCE_OFF beats this)
     */
    case FORCE_ON = 'force_on';

    /**
     * Force the flag to always return false (kill switch).
     *
     * Use case: Emergency disable of problematic features.
     * Precedence: Highest (beats everything, including FORCE_ON)
     */
    case FORCE_OFF = 'force_off';
}
