<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Contracts;

/**
 * Represents a sequence definition.
 *
 * A sequence is identified by a name and optional scope, and defines
 * the pattern, reset behavior, and policies for generating unique numbers.
 */
interface SequenceInterface
{
    /**
     * Get the unique sequence name.
     */
    public function getName(): string;

    /**
     * Get the scope identifier (e.g., tenant_id, branch_id).
     *
     * @return string|null Null for global sequences
     */
    public function getScopeIdentifier(): ?string;

    /**
     * Get the pattern template (e.g., "INV-{YEAR}-{COUNTER:5}").
     */
    public function getPattern(): string;

    /**
     * Get the reset period (never, daily, monthly, yearly).
     */
    public function getResetPeriod(): string;

    /**
     * Get the step size for counter increments.
     */
    public function getStepSize(): int;

    /**
     * Get the counter reset limit (count-based reset).
     *
     * @return int|null Null if no count-based reset
     */
    public function getResetLimit(): ?int;

    /**
     * Get the gap policy (allow_gaps, fill_gaps, report_gaps_only).
     */
    public function getGapPolicy(): string;

    /**
     * Get the overflow behavior (throw_exception, switch_pattern, extend_padding).
     */
    public function getOverflowBehavior(): string;

    /**
     * Get the exhaustion threshold percentage (e.g., 90 for 90%).
     */
    public function getExhaustionThreshold(): int;

    /**
     * Check if the sequence is locked (prevents generation).
     */
    public function isLocked(): bool;

    /**
     * Check if the sequence is active.
     */
    public function isActive(): bool;
}
