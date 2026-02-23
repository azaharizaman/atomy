<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Rules;

use Nexus\SettingsManagement\Contracts\RuleValidationInterface;
use Nexus\SettingsManagement\Contracts\RuleValidationResult;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FlagType;

/**
 * Validates feature flag targeting rules.
 */
final class FeatureFlagTargetingRule implements RuleValidationInterface
{
    public function evaluate(mixed $context): RuleValidationResult
    {
        if (!is_array($context) || !isset($context['flagKey'])) {
            return RuleValidationResult::failure('Invalid context: flagKey required');
        }

        $flagType = $context['flagType'] ?? FlagType::BOOLEAN;
        $targeting = $context['targeting'] ?? [];
        $errors = [];

        // Validate percentage-based targeting
        if ($flagType === FlagType::PERCENTAGE) {
            $percentage = $targeting['percentage'] ?? null;
            if ($percentage !== null) {
                if (!is_int($percentage) || $percentage < 0 || $percentage > 100) {
                    $errors[] = 'Percentage must be an integer between 0 and 100';
                }
            }
        }

        // Validate user-list targeting
        if ($flagType === FlagType::USER_LIST) {
            $userIds = $targeting['userIds'] ?? [];
            if (!is_array($userIds)) {
                $errors[] = 'User IDs must be an array';
            } elseif (empty($userIds)) {
                $errors[] = 'User list targeting requires at least one user ID';
            }
        }

        // Validate IP-based targeting
        if ($flagType === FlagType::IP_BASED) {
            $ipRanges = $targeting['ipRanges'] ?? [];
            if (!is_array($ipRanges)) {
                $errors[] = 'IP ranges must be an array';
            } else {
                foreach ($ipRanges as $ipRange) {
                    if (!$this->isValidCidr($ipRange)) {
                        $errors[] = "Invalid CIDR notation: {$ipRange}";
                    }
                }
            }
        }

        // Validate custom rule targeting
        if ($flagType === FlagType::CUSTOM_RULE) {
            $customRules = $targeting['customRules'] ?? [];
            if (!is_array($customRules)) {
                $errors[] = 'Custom rules must be an array';
            } elseif (empty($customRules)) {
                $errors[] = 'Custom rule targeting requires at least one rule';
            }
        }

        return empty($errors)
            ? RuleValidationResult::success()
            : RuleValidationResult::failure('Targeting validation failed', ['errors' => $errors]);
    }

    private function isValidCidr(string $cidr): bool
    {
        return (bool) preg_match(
            '/^(\d{1,3}\.){3}\d{1,3}(\/\d{1,2})?$/',
            $cidr
        );
    }
}
