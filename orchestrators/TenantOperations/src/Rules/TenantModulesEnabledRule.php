<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Rules;

use Nexus\TenantOperations\Contracts\TenantValidationRuleInterface;
use Nexus\TenantOperations\DTOs\ModulesValidationRequest;
use Nexus\TenantOperations\DTOs\ValidationResult;

/**
 * Rule to validate required modules are enabled for a tenant.
 */
final readonly class TenantModulesEnabledRule implements TenantValidationRuleInterface
{
    public function __construct(
        private ModuleCheckerInterface $moduleChecker,
    ) {}

    public function getName(): string
    {
        return 'tenant_modules_enabled';
    }

    public function getDescription(): string
    {
        return 'Validates that all required modules are enabled for the tenant';
    }

    public function evaluate(mixed $subject): ValidationResult
    {
        if (!$subject instanceof ModulesValidationRequest) {
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

        foreach ($subject->requiredModules as $module) {
            $isEnabled = $this->moduleChecker->isModuleEnabled($tenantId, $module);

            if (!$isEnabled) {
                $errors[] = [
                    'rule' => $this->getName(),
                    'message' => "Module '{$module}' is not enabled for this tenant",
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
 * Interface for checking module enablement.
 */
interface ModuleCheckerInterface
{
    public function isModuleEnabled(string $tenantId, string $moduleKey): bool;
}
