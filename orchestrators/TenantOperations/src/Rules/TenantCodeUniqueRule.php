<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Rules;

use Nexus\TenantOperations\Contracts\TenantCodeCheckerInterface;
use Nexus\TenantOperations\Contracts\TenantValidationRuleInterface;
use Nexus\TenantOperations\DTOs\TenantOnboardingRequest;
use Nexus\TenantOperations\DTOs\ValidationResult;

/**
 * Rule to validate tenant code uniqueness.
 */
final readonly class TenantCodeUniqueRule implements TenantValidationRuleInterface
{
    public function __construct(
        private TenantCodeCheckerInterface $tenantCodeChecker,
    ) {}

    public function getName(): string
    {
        return 'tenant_code_unique';
    }

    public function getDescription(): string
    {
        return 'Validates that the tenant code is unique across the system';
    }

    public function evaluate(mixed $subject): ValidationResult
    {
        if (!$subject instanceof TenantOnboardingRequest) {
            return ValidationResult::failed([
                [
                    'rule' => $this->getName(),
                    'message' => 'Invalid subject type for this rule',
                    'severity' => 'error',
                ],
            ]);
        }

        $isUnique = $this->tenantCodeChecker->isCodeUnique($subject->tenantCode);

        if ($isUnique) {
            return ValidationResult::passed();
        }

        return ValidationResult::failed([
            [
                'rule' => $this->getName(),
                'message' => "Tenant code '{$subject->tenantCode}' is already in use",
                'severity' => 'error',
            ],
        ]);
    }
}
