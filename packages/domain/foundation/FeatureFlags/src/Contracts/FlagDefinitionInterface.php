<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Contracts;

use Nexus\FeatureFlags\Enums\FlagStrategy;
use Nexus\FeatureFlags\Enums\FlagOverride;

/**
 * Represents a feature flag definition with its configuration.
 *
 * This interface defines the structure of a feature flag, including its name,
 * enabled state, evaluation strategy, target value, and optional override.
 */
interface FlagDefinitionInterface
{
    /**
     * Get the unique flag name.
     *
     * Flag names must follow the pattern: /^[a-z0-9_\.]{1,100}$/
     * Examples: 'new_feature', 'module.analytics', 'beta_v2.checkout'
     *
     * @return string The flag name
     */
    public function getName(): string;

    /**
     * Check if the flag is enabled.
     *
     * This is the base enabled state before strategy evaluation.
     * If false, the flag is always disabled regardless of strategy.
     *
     * @return bool True if enabled, false otherwise
     */
    public function isEnabled(): bool;

    /**
     * Get the evaluation strategy.
     *
     * @return FlagStrategy The strategy determining how the flag is evaluated
     */
    public function getStrategy(): FlagStrategy;

    /**
     * Get the strategy-specific value.
     *
     * - SYSTEM_WIDE: null
     * - PERCENTAGE_ROLLOUT: int (0-100)
     * - TENANT_LIST: array of tenant IDs
     * - USER_LIST: array of user IDs
     * - CUSTOM: class-string of CustomEvaluatorInterface implementation
     *
     * @return mixed The value appropriate for the strategy
     */
    public function getValue(): mixed;

    /**
     * Get the override state (force ON/OFF).
     *
     * Overrides take precedence over all other evaluation logic:
     * - FORCE_OFF: Always returns false, regardless of enabled state
     * - FORCE_ON: Always returns true, regardless of enabled state
     * - null: Normal evaluation flow
     *
     * @return FlagOverride|null The override state or null for normal evaluation
     */
    public function getOverride(): ?FlagOverride;

    /**
     * Get additional metadata.
     *
     * Metadata can include:
     * - evaluator_class: For CUSTOM strategy
     * - description: Human-readable description
     * - created_by: User who created the flag
     * - tags: Array of tags for categorization
     *
     * @return array<string, mixed> The metadata array
     */
    public function getMetadata(): array;

    /**
     * Get the checksum for cache validation.
     *
     * The checksum is calculated from the flag's critical properties:
     * enabled, strategy, value, and override. This allows detection of
     * stale cached definitions.
     *
     * @return string SHA-256 hash of the flag's state
     */
    public function getChecksum(): string;
}
