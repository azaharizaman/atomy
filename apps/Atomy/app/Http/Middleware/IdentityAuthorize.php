<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Nexus\Identity\Contracts\PermissionCheckerInterface;
use Nexus\Identity\Contracts\UserInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identity authorization middleware
 * Checks if authenticated user has required permissions
 */
final readonly class IdentityAuthorize
{
    public function __construct(
        private PermissionCheckerInterface $permissionChecker
    ) {
    }

    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        /** @var UserInterface|null $user */
        $user = $request->attributes->get('authenticated_user');

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Authentication required',
            ], 401);
        }

        // Check if user has all required permissions
        foreach ($permissions as $permission) {
            if (!$this->permissionChecker->hasPermission($user, $permission)) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Insufficient permissions',
                    'required_permission' => $permission,
                ], 403);
            }
        }

        return $next($request);
    }
}
