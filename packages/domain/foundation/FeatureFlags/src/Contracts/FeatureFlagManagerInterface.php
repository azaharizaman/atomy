<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Contracts;

use Nexus\FeatureFlags\ValueObjects\EvaluationContext;

/**
 * Main feature flag management interface.
 *
 * This is the primary entry point for evaluating feature flags in your application.
 * It orchestrates repository lookups, context normalization, and evaluation.
 */
interface FeatureFlagManagerInterface
{
    /**
     * Check if a feature flag is enabled.
     *
     * Fail-closed security: If the flag is not found, returns $defaultIfNotFound
     * (which defaults to false for safety).
     *
     * @param string $name The flag name to check
     * @param array<string, mixed>|EvaluationContext $context Context data (array will be normalized to EvaluationContext)
     * @param bool $defaultIfNotFound Value to return if flag doesn't exist (default: false for fail-closed security)
     * @return bool True if the flag is enabled, false otherwise
     *
     * @example
     * // Simple check
     * if ($manager->isEnabled('new_dashboard')) {
     *     // Show new dashboard
     * }
     *
     * // With context
     * if ($manager->isEnabled('premium_features', ['tenant_id' => 'tenant-123'])) {
     *     // Show premium features
     * }
     *
     * // Fail-open for specific flag
     * if ($manager->isEnabled('optional_feature', [], defaultIfNotFound: true)) {
     *     // Show feature if flag not found
     * }
     */
    public function isEnabled(
        string $name,
        array|EvaluationContext $context = [],
        bool $defaultIfNotFound = false
    ): bool;

    /**
     * Check if a feature flag is disabled (inverse of isEnabled).
     *
     * Convenience method for readability in certain contexts.
     *
     * @param string $name The flag name to check
     * @param array<string, mixed>|EvaluationContext $context Context data
     * @param bool $defaultIfNotFound Value to return if flag doesn't exist
     * @return bool True if the flag is disabled, false otherwise
     *
     * @example
     * if ($manager->isDisabled('legacy_mode')) {
     *     // Use new mode
     * }
     */
    public function isDisabled(
        string $name,
        array|EvaluationContext $context = [],
        bool $defaultIfNotFound = false
    ): bool;

    /**
     * Evaluate multiple flags in a single operation (bulk evaluation).
     *
     * More efficient than calling isEnabled() multiple times as it:
     * - Fetches all flags in one repository call
     * - Reuses evaluation context
     * - Batch processes strategy evaluation
     *
     * Flags not found will return false in the result map.
     *
     * @param array<string> $flagNames Array of flag names to evaluate
     * @param array<string, mixed>|EvaluationContext $context Context data
     * @return array<string, bool> Map of flag name => enabled state
     *
     * @example
     * $flags = $manager->evaluateMany([
     *     'new_dashboard',
     *     'advanced_analytics',
     *     'beta_features',
     * ], ['tenant_id' => 'tenant-123']);
     *
     * // Returns: ['new_dashboard' => true, 'advanced_analytics' => false, 'beta_features' => true]
     *
     * if ($flags['new_dashboard']) {
     *     // Show new dashboard
     * }
     */
    public function evaluateMany(
        array $flagNames,
        array|EvaluationContext $context = []
    ): array;

    /**
     * Check if audit change tracking is available.
     *
     * When true, all flag modifications are recorded via FlagAuditChangeInterface.
     * Application layer implements this using Nexus\AuditLogger for compliance.
     *
     * @return bool True if audit change interface is configured
     */
    public function hasAuditChange(): bool;

    /**
     * Check if audit query capability is available.
     *
     * When true, historical flag states can be queried via FlagAuditQueryInterface.
     * Application layer implements this using Nexus\EventStream for compliance.
     *
     * @return bool True if audit query interface is configured
     */
    public function hasAuditQuery(): bool;

    /**
     * Get the audit query interface for historical queries.
     *
     * Use this to query flag change history, state at point in time, etc.
     *
     * @return FlagAuditQueryInterface|null The audit query interface, or null if not configured
     *
     * @example
     * if ($manager->hasAuditQuery()) {
     *     $history = $manager->getAuditQuery()->getHistory('payment_v2');
     *     $stateAt = $manager->getAuditQuery()->getStateAt(
     *         'payment_v2',
     *         new DateTimeImmutable('2024-11-15')
     *     );
     * }
     */
    public function getAuditQuery(): ?FlagAuditQueryInterface;
}
