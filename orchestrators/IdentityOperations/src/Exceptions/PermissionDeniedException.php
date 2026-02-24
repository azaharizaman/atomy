<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Exceptions;

/**
 * Exception thrown when a permission check fails.
 */
class PermissionDeniedException extends IdentityOperationsException
{
    public static function forPermission(string $userId, string $permission, ?string $tenantId = null): self
    {
        $context = ['user_id' => $userId, 'permission' => $permission];
        if ($tenantId !== null) {
            $context['tenant_id'] = $tenantId;
        }
        
        return new self(
            "User '{$userId}' does not have permission '{$permission}'" .
            ($tenantId !== null ? " in tenant '{$tenantId}'" : ''),
            $context
        );
    }

    public static function forRole(string $userId, string $role, ?string $tenantId = null): self
    {
        $context = ['user_id' => $userId, 'role' => $role];
        if ($tenantId !== null) {
            $context['tenant_id'] = $tenantId;
        }
        
        return new self(
            "User '{$userId}' does not have role '{$role}'" .
            ($tenantId !== null ? " in tenant '{$tenantId}'" : ''),
            $context
        );
    }
}
