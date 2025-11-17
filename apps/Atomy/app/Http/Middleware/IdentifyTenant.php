<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Nexus\Tenant\Services\TenantContextManager;
use Nexus\Tenant\Services\TenantResolverService;
use Nexus\Tenant\ValueObjects\IdentificationStrategy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identify Tenant Middleware
 *
 * Automatically resolves and sets the tenant context from the request.
 */
class IdentifyTenant
{
    public function __construct(
        private readonly TenantResolverService $resolver,
        private readonly TenantContextManager $contextManager
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $strategy = IdentificationStrategy::fromString(
            config('tenant.identification_strategy', 'subdomain')
        );

        $context = [
            'domain' => $request->getHost(),
            'headers' => $request->headers->all(),
            'path' => $request->getPathInfo(),
            'header_name' => config('tenant.header_name', 'X-Tenant-ID'),
            'path_prefix' => config('tenant.path_prefix', '/tenant/'),
        ];

        $tenantId = $this->resolver->resolve($strategy, $context);

        if ($tenantId) {
            $this->contextManager->setTenant($tenantId);
        }

        return $next($request);
    }
}
