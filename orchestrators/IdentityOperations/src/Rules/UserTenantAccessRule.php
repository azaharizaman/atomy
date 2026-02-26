<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Rules;

use Nexus\IdentityOperations\Contracts\UserValidationRuleInterface;
use Nexus\IdentityOperations\DTOs\ValidationResult;

/**
 * Rule to validate user has access to tenant.
 */
final readonly class UserTenantAccessRule implements UserValidationRuleInterface
{
    public function __construct(
        private TenantAccessCheckerInterface $tenantAccessChecker,
    ) {}

    public function getName(): string
    {
        return 'user_tenant_access';
    }

    public function getDescription(): string
    {
        return 'Validates that user has access to the specified tenant';
    }

    public function evaluate(mixed $subject): ValidationResult
    {
        if (!is_array($subject) || !isset($subject['user_id'], $subject['tenant_id'])) {
            return ValidationResult::failed([
                [
                    'rule' => $this->getName(),
                    'message' => 'Invalid subject type for this rule. Expected array with user_id and tenant_id.',
                    'severity' => 'error',
                ],
            ]);
        }

        $userId = $subject['user_id'];
        $tenantId = $subject['tenant_id'];

        $hasAccess = $this->tenantAccessChecker->hasAccess($userId, $tenantId);

        if (!$hasAccess) {
            return ValidationResult::failed([
                [
                    'rule' => $this->getName(),
                    'message' => "User '{$userId}' does not have access to tenant '{$tenantId}'",
                    'severity' => 'error',
                ],
            ]);
        }

        return ValidationResult::passed();
    }
}
