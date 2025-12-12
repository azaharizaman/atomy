<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Result of a SOX control validation.
 */
enum SOXControlResult: string
{
    /**
     * Control passed successfully.
     */
    case PASSED = 'passed';

    /**
     * Control failed - requires remediation.
     */
    case FAILED = 'failed';

    /**
     * Control was skipped (not applicable or disabled).
     */
    case SKIPPED = 'skipped';

    /**
     * Control requires manual review.
     */
    case PENDING_REVIEW = 'pending_review';

    /**
     * Control failed but was overridden with proper authorization.
     */
    case OVERRIDDEN = 'overridden';

    /**
     * Control not executed due to system error.
     */
    case ERROR = 'error';

    /**
     * Control execution timed out.
     */
    case TIMEOUT = 'timeout';

    /**
     * Check if this result allows the transaction to proceed.
     */
    public function allowsProceeding(): bool
    {
        return match ($this) {
            self::PASSED, self::SKIPPED, self::OVERRIDDEN => true,
            default => false,
        };
    }

    /**
     * Check if this result requires investigation.
     */
    public function requiresInvestigation(): bool
    {
        return match ($this) {
            self::FAILED, self::ERROR, self::TIMEOUT => true,
            default => false,
        };
    }

    /**
     * Check if this result needs audit documentation.
     */
    public function requiresAuditDocumentation(): bool
    {
        return match ($this) {
            self::OVERRIDDEN, self::FAILED => true,
            default => false,
        };
    }

    /**
     * Get the severity level for reporting (1-5, 5 being most severe).
     */
    public function getSeverity(): int
    {
        return match ($this) {
            self::PASSED, self::SKIPPED => 1,
            self::PENDING_REVIEW => 2,
            self::OVERRIDDEN => 3,
            self::TIMEOUT, self::ERROR => 4,
            self::FAILED => 5,
        };
    }

    /**
     * Get a human-readable description.
     */
    public function description(): string
    {
        return match ($this) {
            self::PASSED => 'Control validation passed',
            self::FAILED => 'Control validation failed',
            self::SKIPPED => 'Control was skipped',
            self::PENDING_REVIEW => 'Awaiting manual review',
            self::OVERRIDDEN => 'Failed but overridden with authorization',
            self::ERROR => 'System error during validation',
            self::TIMEOUT => 'Validation timed out',
        };
    }
}
