<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Rules;

use Nexus\SettingsManagement\Contracts\RuleValidationInterface;
use Nexus\SettingsManagement\Contracts\RuleValidationResult;
use Nexus\SettingsManagement\DTOs\Settings\SettingValidationRequest;

/**
 * Validates setting value format and type.
 */
final class SettingValueValidRule implements RuleValidationInterface
{
    private const VALID_TYPES = ['string', 'integer', 'boolean', 'array', 'number'];

    public function evaluate(mixed $context): RuleValidationResult
    {
        if (!$context instanceof SettingValidationRequest) {
            return RuleValidationResult::failure('Invalid context type');
        }

        $errors = [];

        // Validate key format
        if (empty($context->key)) {
            $errors[] = 'Setting key cannot be empty';
        }

        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_.]*$/', $context->key)) {
            $errors[] = 'Setting key must start with a letter and contain only alphanumeric characters, dots, and underscores';
        }

        // Validate value type
        $valueType = gettype($context->value);
        if (!in_array($valueType, self::VALID_TYPES, true)) {
            $errors[] = "Invalid value type: {$valueType}. Allowed types: " . implode(', ', self::VALID_TYPES);
        }

        // Validate array values have string keys
        if (is_array($context->value)) {
            foreach ($context->value as $key => $val) {
                if (!is_string($key)) {
                    $errors[] = 'Array values must have string keys';
                    break;
                }
            }
        }

        return empty($errors) 
            ? RuleValidationResult::success(['key' => $context->key])
            : RuleValidationResult::failure('Setting validation failed', ['errors' => $errors]);
    }
}
