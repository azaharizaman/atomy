<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Nexus\Identity\Contracts\PermissionCheckerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Admin Role Middleware
 * 
 * Ensures user has 'admin' role before accessing admin-restricted resources.
 * Used in Filament panels for security.
 */
class CheckAdminRole
{
    public function __construct(
        private readonly PermissionCheckerInterface $permissionChecker
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            abort(401, 'Unauthenticated.');
        }

        $user = auth()->user();

        // Check if user has admin role via Identity package
        if (!$this->permissionChecker->hasRole($user, 'admin')) {
            abort(403, 'Access denied. Admin role required.');
        }

        return $next($request);
    }
}
