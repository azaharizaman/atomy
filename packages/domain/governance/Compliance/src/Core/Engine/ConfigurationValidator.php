<?php

declare(strict_types=1);

namespace Nexus\Compliance\Core\Engine;

use Psr\Log\LoggerInterface;

/**
 * Configuration validator for compliance schemes.
 * 
 * Validates that required features and settings are configured correctly.
 */
final class ConfigurationValidator
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Validate that required features are enabled.
     *
     * @param array<string> $requiredFeatures List of required feature names
     * @param array<string> $enabledFeatures List of currently enabled features
     * @return array<string> Array of missing features (empty if all present)
     */
    public function validateRequiredFeatures(
        array $requiredFeatures,
        array $enabledFeatures
    ): array {
        $this->logger->debug("Validating required features", [
            'required_count' => count($requiredFeatures),
            'enabled_count' => count($enabledFeatures),
        ]);

        $missingFeatures = array_diff($requiredFeatures, $enabledFeatures);

        if (!empty($missingFeatures)) {
            $this->logger->warning("Missing required features", [
                'missing' => $missingFeatures,
            ]);
        }

        return array_values($missingFeatures);
    }

    /**
     * Validate that required settings are configured.
     *
     * @param array<string, mixed> $requiredSettings Map of setting name => expected type
     * @param array<string, mixed> $currentSettings Map of setting name => current value
     * @return array<string> Array of validation errors (empty if valid)
     */
    public function validateRequiredSettings(
        array $requiredSettings,
        array $currentSettings
    ): array {
        $this->logger->debug("Validating required settings", [
            'required_count' => count($requiredSettings),
            'current_count' => count($currentSettings),
        ]);

        $errors = [];

        foreach ($requiredSettings as $settingName => $expectedType) {
            if (!isset($currentSettings[$settingName])) {
                $errors[] = "Missing required setting: {$settingName}";
                continue;
            }

            $actualType = gettype($currentSettings[$settingName]);
            if ($actualType !== $expectedType) {
                $errors[] = "Setting '{$settingName}' has wrong type: expected {$expectedType}, got {$actualType}";
            }
        }

        if (!empty($errors)) {
            $this->logger->warning("Setting validation failed", [
                'error_count' => count($errors),
            ]);
        }

        return $errors;
    }

    /**
     * Validate that required fields exist on entities.
     *
     * @param string $entityType The entity type being validated
     * @param array<string> $requiredFields List of required field names
     * @param array<string> $availableFields List of available field names
     * @return array<string> Array of missing fields (empty if all present)
     */
    public function validateRequiredFields(
        string $entityType,
        array $requiredFields,
        array $availableFields
    ): array {
        $this->logger->debug("Validating required fields", [
            'entity_type' => $entityType,
            'required_count' => count($requiredFields),
            'available_count' => count($availableFields),
        ]);

        $missingFields = array_diff($requiredFields, $availableFields);

        if (!empty($missingFields)) {
            $this->logger->warning("Missing required fields", [
                'entity_type' => $entityType,
                'missing' => $missingFields,
            ]);
        }

        return array_values($missingFields);
    }

    /**
     * Validate configuration against a compliance scheme.
     *
     * @param string $schemeName The compliance scheme name
     * @param array<string, mixed> $schemeConfiguration The scheme configuration
     * @param array<string, mixed> $systemConfiguration The current system configuration
     * @return array<string> Array of validation errors (empty if valid)
     */
    public function validateSchemeConfiguration(
        string $schemeName,
        array $schemeConfiguration,
        array $systemConfiguration
    ): array {
        $this->logger->info("Validating scheme configuration", [
            'scheme_name' => $schemeName,
        ]);

        $errors = [];

        // Validate required features
        if (isset($schemeConfiguration['required_features'])) {
            $enabledFeatures = $systemConfiguration['enabled_features'] ?? [];
            $missingFeatures = $this->validateRequiredFeatures(
                $schemeConfiguration['required_features'],
                $enabledFeatures
            );

            foreach ($missingFeatures as $feature) {
                $errors[] = "Required feature not enabled: {$feature}";
            }
        }

        // Validate required settings
        if (isset($schemeConfiguration['required_settings'])) {
            $currentSettings = $systemConfiguration['settings'] ?? [];
            $settingErrors = $this->validateRequiredSettings(
                $schemeConfiguration['required_settings'],
                $currentSettings
            );

            $errors = array_merge($errors, $settingErrors);
        }

        // Validate required fields on entities
        if (isset($schemeConfiguration['required_entity_fields'])) {
            foreach ($schemeConfiguration['required_entity_fields'] as $entityType => $requiredFields) {
                $availableFields = $systemConfiguration['entity_fields'][$entityType] ?? [];
                $missingFields = $this->validateRequiredFields(
                    $entityType,
                    $requiredFields,
                    $availableFields
                );

                foreach ($missingFields as $field) {
                    $errors[] = "Entity '{$entityType}' is missing required field: {$field}";
                }
            }
        }

        if (empty($errors)) {
            $this->logger->info("Scheme configuration validation passed", [
                'scheme_name' => $schemeName,
            ]);
        } else {
            $this->logger->warning("Scheme configuration validation failed", [
                'scheme_name' => $schemeName,
                'error_count' => count($errors),
            ]);
        }

        return $errors;
    }
}
