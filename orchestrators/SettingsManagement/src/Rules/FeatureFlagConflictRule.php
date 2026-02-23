<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Rules;

use Nexus\SettingsManagement\Contracts\RuleValidationInterface;
use Nexus\SettingsManagement\Contracts\RuleValidationResult;

/**
 * Detects conflicting feature flags.
 * Ensures mutually exclusive features are not enabled simultaneously.
 */
final class FeatureFlagConflictRule implements RuleValidationInterface
{
    /**
     * Map of conflicting flag pairs.
     *
     * @var array<string, array<int, string>>
     */
    private array $conflicts = [
        'new_dashboard' => ['legacy_dashboard'],
        'graphql_api' => ['rest_api'],
        'modern_checkout' => ['classic_checkout'],
    ];

    public function evaluate(mixed $context): RuleValidationResult
    {
        if (!is_array($context) || !isset($context['flagKey'])) {
            return RuleValidationResult::failure('Invalid context: flagKey required');
        }

        $flagKey = $context['flagKey'];
        $tenantId = $context['tenantId'] ?? null;
        $targeting = $context['targeting'] ?? [];
        $currentFlags = $context['currentFlags'] ?? [];

        // Check if enabling the flag
        $isEnabled = $targeting['enabled'] ?? false;
        if (!$isEnabled) {
            return RuleValidationResult::success();
        }

        if (!isset($this->conflicts[$flagKey])) {
            return RuleValidationResult::success();
        }

        $conflictingFlags = $this->conflicts[$flagKey];
        $activeConflicts = [];

        foreach ($conflictingFlags as $conflict) {
            if (isset($currentFlags[$conflict]) && $currentFlags[$conflict] === true) {
                $activeConflicts[] = $conflict;
            }
        }

        if (!empty($activeConflicts)) {
            return RuleValidationResult::failure(
                "Feature flag '{$flagKey}' conflicts with active flags: " . implode(', ', $activeConflicts),
                ['conflicting_flags' => $activeConflicts]
            );
        }

        return RuleValidationResult::success();
    }

    /**
     * Register a conflict between two feature flags.
     */
    public function registerConflict(string $flagKey1, string $flagKey2): void
    {
        $this->conflicts[$flagKey1][] = $flagKey2;
        $this->conflicts[$flagKey2][] = $flagKey1;
    }
}
