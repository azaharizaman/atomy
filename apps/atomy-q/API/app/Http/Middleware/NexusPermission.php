<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Nexus\Identity\Contracts\PermissionCheckerInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class NexusPermission
{
    public function __construct(
        private PermissionCheckerInterface $permissionChecker,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $userId = $request->attributes->get('auth_user_id');
        $tenantId = $request->attributes->get('auth_tenant_id');

        if (!$userId || !$tenantId) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        try {
            $user = $this->userRepository->findById((string) $userId);
            if ($this->permissionChecker->hasPermission($user, $permission)) {
                return $next($request);
            }
        } catch (\Throwable) {
            return response()->json(['error' => 'Permission check failed'], 403);
        }

        return response()->json(['error' => 'Forbidden'], 403);
    }
}
