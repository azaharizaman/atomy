<?php

declare(strict_types=1);

namespace Nexus\Localization\Exceptions;

/**
 * Exception thrown when a Phase 2 feature is invoked.
 *
 * Phase 1 focuses on formatting (numbers, dates, currency).
 * Phase 2 will implement the translation system.
 */
final class FeatureNotImplementedException extends LocalizationException
{
    public function __construct(string $featureName)
    {
        parent::__construct(
            "Feature not yet implemented: {$featureName}. " .
            "This feature is planned for Phase 2."
        );
    }
}
