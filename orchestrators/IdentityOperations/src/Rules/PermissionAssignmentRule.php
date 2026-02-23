<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Rules;

use Nexus\IdentityOperations\Contracts\UserValidationRuleInterface;
use Nexus\IdentityOperations\DTOs\ValidationResult;

/**
 * Rule to validate permission can be assigned.
 */
final readonly class PermissionAssignmentRule implements UserValidationRuleInterface
{
    public function __construct(
        private PermissionValidatorInterface $permissionValidator,
    ) {}

    public function getName(): string
    {
        return 'permission_assignment';
    }

    public function getDescription(): string
    {
        return 'Validates that permission can be assigned to user';
    }

    public function evaluate(mixed $subject): ValidationResult
    {
        if (!is_array($subject) || !isset($subject['permission'], $subject['user_id'])) {
            return ValidationResult::failed([
                [
                    'rule' => $this->getName(),
                    'message' => 'Invalid subject type for this rule. Expected array with permission and user_id.',
                    'severity' => 'error',
                ],
            ]);
        }

        $permission = $subject['permission'];
        $userId = $subject['user_id'];
        $tenantId = $subject['tenant_id'] ?? null;

        // Check if permission exists
        if (!$this->permissionValidator->permissionExists($permission)) {
            return ValidationResult::failed([
                [
                    'rule' => $this->getName(),
                    'message' => "Permission '{$permission}' does not exist",
                    'severity' => 'error',
                ],
            ]);
        }

        // Check if user already has this permission
        if ($this->permissionValidator->userHasPermission($userId, $permission, $tenantId)) {
            return ValidationResult::passed()->withWarning(
                $this->getName(),
                "User '{$userId}' already has permission '{$permission}'",
                'warning',
            );
        }

        return ValidationResult::passed();
    }
}

/**
 * Interface for validating permissions.
 */
interface PermissionValidatorInterface
{
    public function permissionExists(string $permission): bool;

    public function userHasPermission(string $userId, string $permission, ?string $tenantId = null): bool;
}
