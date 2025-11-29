<?php

declare(strict_types=1);

namespace App\Security\Attribute;

use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Custom security attribute for Identity permission checks.
 * 
 * This attribute marks a controller method as requiring a specific Identity permission.
 * The IdentityVoter handles the actual authorization check.
 * 
 * Usage:
 *   #[RequiresPermission('user', 'create')]   // Requires IDENTITY_USER_CREATE
 *   #[RequiresPermission('role', 'delete')]   // Requires IDENTITY_ROLE_DELETE
 * 
 * The attribute is processed by wrapping it with Symfony's IsGranted attribute
 * in the controller where it's applied.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class RequiresPermission
{
    public readonly string $attribute;
    public readonly string $message;
    public readonly int $statusCode;

    public function __construct(
        public readonly string $resource,
        public readonly string $action,
        ?string $message = null,
        int $statusCode = 403,
    ) {
        $this->attribute = 'IDENTITY_' . strtoupper($resource) . '_' . strtoupper($action);
        $this->message = $message ?? "Permission denied: requires {$resource}:{$action}";
        $this->statusCode = $statusCode;
    }
}
