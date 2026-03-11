<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->attributes->get('auth_tenant_id');

        if ($tenantId === null || $tenantId === '') {
            return response()->json(['error' => 'Tenant context required'], 403);
        }

        return $next($request);
    }
}
