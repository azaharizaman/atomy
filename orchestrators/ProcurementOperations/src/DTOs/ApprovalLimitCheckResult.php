<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Result DTO for approval limit checks.
 *
 * Contains the result of validating whether a user can approve
 * a document at a specific amount, including the effective limit
 * and any warnings.
 */
final readonly class ApprovalLimitCheckResult
{
    /**
     * @param bool $isWithinLimit Whether the amount is within the user's limit
     * @param int $effectiveLimitCents The user's effective limit for this document type (in cents)
     * @param int $requestedAmountCents The amount being validated (in cents)
     * @param string $limitSource Source of the effective limit (default, role, department, user_override)
     * @param string|null $limitSourceId Identifier of the source (role ID, department ID, etc.)
     * @param int $exceedanceAmountCents Amount exceeding limit (0 if within limit)
     * @param bool $hasWarnings Whether there are any warnings
     * @param array<string> $warnings Warning messages
     * @param string|null $escalationRequired Level of escalation required if over limit
     * @param string|null $reason Human-readable explanation
     */
    public function __construct(
        public bool $isWithinLimit,
        public int $effectiveLimitCents,
        public int $requestedAmountCents,
        public string $limitSource,
        public ?string $limitSourceId = null,
        public int $exceedanceAmountCents = 0,
        public bool $hasWarnings = false,
        public array $warnings = [],
        public ?string $escalationRequired = null,
        public ?string $reason = null,
    ) {}

    /**
     * Create a successful result (amount within limit).
     */
    public static function approved(
        int $effectiveLimitCents,
        int $requestedAmountCents,
        string $limitSource,
        ?string $limitSourceId = null,
        array $warnings = [],
    ): self {
        return new self(
            isWithinLimit: true,
            effectiveLimitCents: $effectiveLimitCents,
            requestedAmountCents: $requestedAmountCents,
            limitSource: $limitSource,
            limitSourceId: $limitSourceId,
            exceedanceAmountCents: 0,
            hasWarnings: ! empty($warnings),
            warnings: $warnings,
            escalationRequired: null,
            reason: 'Amount is within approval authority',
        );
    }

    /**
     * Create a failure result (amount exceeds limit).
     */
    public static function exceeds(
        int $effectiveLimitCents,
        int $requestedAmountCents,
        string $limitSource,
        ?string $limitSourceId = null,
        ?string $escalationRequired = null,
        array $warnings = [],
    ): self {
        $exceedance = $requestedAmountCents - $effectiveLimitCents;

        return new self(
            isWithinLimit: false,
            effectiveLimitCents: $effectiveLimitCents,
            requestedAmountCents: $requestedAmountCents,
            limitSource: $limitSource,
            limitSourceId: $limitSourceId,
            exceedanceAmountCents: max(0, $exceedance),
            hasWarnings: ! empty($warnings),
            warnings: $warnings,
            escalationRequired: $escalationRequired,
            reason: sprintf(
                'Amount exceeds approval limit by %s cents. Escalation to %s required.',
                number_format($exceedance),
                $escalationRequired ?? 'higher authority'
            ),
        );
    }

    /**
     * Create a result for when user has no approval authority.
     */
    public static function noAuthority(
        int $requestedAmountCents,
        string $reason,
    ): self {
        return new self(
            isWithinLimit: false,
            effectiveLimitCents: 0,
            requestedAmountCents: $requestedAmountCents,
            limitSource: 'none',
            limitSourceId: null,
            exceedanceAmountCents: $requestedAmountCents,
            hasWarnings: false,
            warnings: [],
            escalationRequired: 'higher_authority',
            reason: $reason,
        );
    }

    /**
     * Check if escalation is required.
     */
    public function requiresEscalation(): bool
    {
        return ! $this->isWithinLimit && $this->escalationRequired !== null;
    }

    /**
     * Get the percentage of limit used.
     */
    public function getLimitUtilization(): float
    {
        if ($this->effectiveLimitCents === 0) {
            return 100.0;
        }

        if ($this->effectiveLimitCents === PHP_INT_MAX) {
            return 0.0;
        }

        return ($this->requestedAmountCents / $this->effectiveLimitCents) * 100.0;
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'is_within_limit' => $this->isWithinLimit,
            'effective_limit_cents' => $this->effectiveLimitCents,
            'requested_amount_cents' => $this->requestedAmountCents,
            'limit_source' => $this->limitSource,
            'limit_source_id' => $this->limitSourceId,
            'exceedance_amount_cents' => $this->exceedanceAmountCents,
            'has_warnings' => $this->hasWarnings,
            'warnings' => $this->warnings,
            'escalation_required' => $this->escalationRequired,
            'reason' => $this->reason,
            'limit_utilization_percent' => $this->getLimitUtilization(),
        ];
    }
}
