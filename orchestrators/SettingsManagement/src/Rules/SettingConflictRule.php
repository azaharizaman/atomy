<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Rules;

use Nexus\SettingsManagement\Contracts\RuleValidationInterface;
use Nexus\SettingsManagement\Contracts\RuleValidationResult;

/**
 * Detects conflicting settings.
 * Ensures mutually exclusive settings are not enabled simultaneously.
 */
final class SettingConflictRule implements RuleValidationInterface
{
    /**
     * Map of conflicting setting pairs.
     *
     * @var array<string, array<int, string>>
     */
    private array $conflicts = [
        'payment.stripe' => ['payment.paypal'],
        'billing.monthly' => ['billing.yearly'],
        'feature.legacy_ui' => ['feature.modern_ui'],
    ];

    public function evaluate(mixed $context): RuleValidationResult
    {
        if (!is_array($context) || !isset($context['key'])) {
            return RuleValidationResult::failure('Invalid context');
        }

        $key = $context['key'];
        $value = $context['value'] ?? null;
        $currentSettings = $context['currentSettings'] ?? [];

        // Only check conflicts when enabling a setting (value is truthy)
        if (!$value) {
            return RuleValidationResult::success();
        }

        if (!isset($this->conflicts[$key])) {
            return RuleValidationResult::success();
        }

        $conflictingKeys = $this->conflicts[$key];
        $activeConflicts = [];

        foreach ($conflictingKeys as $conflict) {
            if (isset($currentSettings[$conflict]) && $currentSettings[$conflict] === true) {
                $activeConflicts[] = $conflict;
            }
        }

        if (!empty($activeConflicts)) {
            return RuleValidationResult::failure(
                "Setting '{$key}' conflicts with the following active settings: " . implode(', ', $activeConflicts),
                ['conflicting_settings' => $activeConflicts]
            );
        }

        return RuleValidationResult::success();
    }

    /**
     * Register a conflict between two settings.
     */
    public function registerConflict(string $key1, string $key2): void
    {
        $this->conflicts[$key1][] = $key2;
        $this->conflicts[$key2][] = $key1;
    }
}
