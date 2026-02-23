<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Rules;

use Nexus\TenantOperations\Contracts\TenantValidationRuleInterface;
use Nexus\TenantOperations\DTOs\ConfigurationValidationRequest;
use Nexus\TenantOperations\DTOs\ValidationResult;

/**
 * Rule to validate required configurations exist for a tenant.
 */
final readonly class TenantConfigurationExistsRule implements TenantValidationRuleInterface
{
    public function __construct(
        private ConfigurationCheckerInterface $configChecker,
    ) {}

    public function getName(): string
    {
        return 'tenant_configuration_exists';
    }

    public function getDescription(): string
    {
        return 'Validates that all required configurations are set for the tenant';
    }

    public function evaluate(mixed $subject): ValidationResult
    {
        if (!$subject instanceof ConfigurationValidationRequest) {
            return ValidationResult::failed([
                [
                    'rule' => $this->getName(),
                    'message' => 'Invalid subject type for this rule',
                    'severity' => 'error',
                ],
            ]);
        }

        $errors = [];
        $tenantId = $subject->tenantId;

        foreach ($subject->requiredConfigs as $config) {
            $exists = $this->configChecker->configurationExists($tenantId, $config);

            if (!$exists) {
                $errors[] = [
                    'rule' => $this->getName(),
                    'message' => "Configuration '{$config}' is not set for this tenant",
                    'severity' => 'error',
                ];
            }
        }

        if (empty($errors)) {
            return ValidationResult::passed();
        }

        return ValidationResult::failed($errors);
    }
}

/**
 * Interface for checking configuration existence.
 */
interface ConfigurationCheckerInterface
{
    public function configurationExists(string $tenantId, string $configKey): bool;
}
