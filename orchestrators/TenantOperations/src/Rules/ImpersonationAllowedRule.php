<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Rules;

use Nexus\TenantOperations\Contracts\TenantValidationRuleInterface;
use Nexus\TenantOperations\DTOs\ImpersonationStartRequest;
use Nexus\TenantOperations\DTOs\ValidationResult;

/**
 * Rule to validate impersonation is allowed for an admin.
 */
final readonly class ImpersonationAllowedRule implements TenantValidationRuleInterface
{
    public function __construct(
        private ImpersonationPermissionCheckerInterface $permissionChecker,
    ) {}

    public function getName(): string
    {
        return 'impersonation_allowed';
    }

    public function getDescription(): string
    {
        return 'Validates that the admin has permission to impersonate tenants';
    }

    public function evaluate(mixed $subject): ValidationResult
    {
        if (!$subject instanceof ImpersonationStartRequest) {
            return ValidationResult::failed([
                [
                    'rule' => $this->getName(),
                    'message' => 'Invalid subject type for this rule',
                    'severity' => 'error',
                ],
            ]);
        }

        $isAllowed = $this->permissionChecker->hasImpersonationPermission($subject->adminUserId);

        if ($isAllowed) {
            return ValidationResult::passed();
        }

        return ValidationResult::failed([
            [
                'rule' => $this->getName(),
                'message' => 'Admin does not have permission to impersonate tenants',
                'severity' => 'error',
            ],
        ]);
    }
}
