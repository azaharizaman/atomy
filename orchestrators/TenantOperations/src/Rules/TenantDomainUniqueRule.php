<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Rules;

use Nexus\TenantOperations\Contracts\TenantDomainCheckerInterface;
use Nexus\TenantOperations\Contracts\TenantValidationRuleInterface;
use Nexus\TenantOperations\DTOs\TenantOnboardingRequest;
use Nexus\TenantOperations\DTOs\ValidationResult;

/**
 * Rule to validate tenant domain uniqueness.
 */
final readonly class TenantDomainUniqueRule implements TenantValidationRuleInterface
{
    public function __construct(
        private TenantDomainCheckerInterface $tenantDomainChecker,
    ) {}

    public function getName(): string
    {
        return 'tenant_domain_unique';
    }

    public function getDescription(): string
    {
        return 'Validates that the tenant domain is unique across the system';
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

        $isUnique = $this->tenantDomainChecker->isDomainUnique($subject->domain);

        if ($isUnique) {
            return ValidationResult::passed();
        }

        return ValidationResult::failed([
            [
                'rule' => $this->getName(),
                'message' => "Tenant domain '{$subject->domain}' is already in use",
                'severity' => 'error',
            ],
        ]);
    }
}
