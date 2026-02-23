<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\Rules;

use Nexus\SettingsManagement\Contracts\RuleValidationInterface;
use Nexus\SettingsManagement\Contracts\RuleValidationResult;

/**
 * Validates configuration import data structure.
 */
final class ConfigurationImportRule implements RuleValidationInterface
{
    private const REQUIRED_VERSION_KEYS = ['version', 'exported_at', 'tenant_id'];
    private const VALID_SECTIONS = ['settings', 'feature_flags', 'fiscal_periods', 'fiscal_calendar'];

    public function evaluate(mixed $context): RuleValidationResult
    {
        if (!is_array($context) || !isset($context['jsonData'])) {
            return RuleValidationResult::failure('Invalid context: jsonData required');
        }

        $jsonData = $context['jsonData'];
        $errors = [];

        // Decode JSON
        $data = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return RuleValidationResult::failure(
                'Invalid JSON: ' . json_last_error_msg(),
                ['json_error' => json_last_error()]
            );
        }

        // Validate required top-level keys
        foreach (self::REQUIRED_VERSION_KEYS as $key) {
            if (!isset($data[$key])) {
                $errors[] = "Missing required key: {$key}";
            }
        }

        // Validate version format
        if (isset($data['version']) && !preg_match('/^\d+\.\d+\.\d+$/', $data['version'])) {
            $errors[] = 'Invalid version format (expected semantic versioning)';
        }

        // Validate sections
        $presentSections = array_keys(array_filter($data, fn($v) => is_array($v), ARRAY_FILTER_USE_KEY));
        foreach ($presentSections as $section) {
            if (!in_array($section, self::VALID_SECTIONS, true)) {
                $errors[] = "Unknown section: {$section}";
            }
        }

        // Validate settings structure
        if (isset($data['settings']) && is_array($data['settings'])) {
            foreach ($data['settings'] as $key => $value) {
                if (!is_string($key) || empty($key)) {
                    $errors[] = 'Settings must have string keys';
                    break;
                }
            }
        }

        // Validate feature flags structure
        if (isset($data['feature_flags']) && is_array($data['feature_flags'])) {
            foreach ($data['feature_flags'] as $flag) {
                if (!is_array($flag)) {
                    $errors[] = 'Feature flags must be arrays';
                    break;
                }
                if (!isset($flag['key'])) {
                    $errors[] = 'Feature flag missing required key: key';
                    break;
                }
            }
        }

        return empty($errors)
            ? RuleValidationResult::success(['validated_sections' => $presentSections])
            : RuleValidationResult::failure('Configuration validation failed', ['errors' => $errors]);
    }
}
