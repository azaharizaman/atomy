<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Rules;

use Nexus\SettingsManagement\Contracts\RuleValidationInterface;
use Nexus\SettingsManagement\Contracts\RuleValidationResult;

/**
 * Validates setting dependencies.
 * Ensures dependent settings are configured before enabling a setting.
 */
final class SettingDependencyRule implements RuleValidationInterface
{
    /**
     * Map of setting keys to their dependencies.
     *
     * @var array<string, array<int, string>>
     */
    private array $dependencies = [
        'billing.enabled' => ['payment.gateway'],
        'feature.advanced_reporting' => ['feature.basic_reporting'],
        'integration.sap' => ['integration.soap'],
    ];

    public function evaluate(mixed $context): RuleValidationResult
    {
        if (!is_array($context) || !isset($context['key'])) {
            return RuleValidationResult::failure('Invalid context');
        }

        $key = $context['key'];
        $tenantId = $context['tenantId'] ?? null;
        $currentSettings = $context['currentSettings'] ?? [];

        if (!isset($this->dependencies[$key])) {
            return RuleValidationResult::success();
        }

        $requiredDeps = $this->dependencies[$key];
        $missingDeps = [];

        foreach ($requiredDeps as $dep) {
            if (!isset($currentSettings[$dep]) || $currentSettings[$dep] !== true) {
                $missingDeps[] = $dep;
            }
        }

        if (!empty($missingDeps)) {
            return RuleValidationResult::failure(
                "Setting '{$key}' requires the following dependencies to be enabled: " . implode(', ', $missingDeps),
                ['missing_dependencies' => $missingDeps]
            );
        }

        return RuleValidationResult::success();
    }

    /**
     * Register a dependency for a setting.
     */
    public function registerDependency(string $key, string $dependency): void
    {
        if (!isset($this->dependencies[$key])) {
            $this->dependencies[$key] = [];
        }
        $this->dependencies[$key][] = $dependency;
    }
}
