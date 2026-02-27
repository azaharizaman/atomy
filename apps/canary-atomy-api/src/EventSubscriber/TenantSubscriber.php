<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\TenantContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Tenant Context Subscriber.
 *
 * Extracts tenant ID from the request and sets the current tenant context.
 * This runs on every request to ensure tenant context is available for all operations.
 */
final class TenantSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TenantContext $tenantContext
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 30],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Skip if this is a sub-request
        if (!$event->isMainRequest()) {
            return;
        }

        // Skip if tenant context is already set
        if ($this->tenantContext->hasTenant()) {
            return;
        }

        // Try to extract tenant ID from various sources
        $tenantId = $this->extractTenantId($request);

        if ($tenantId !== null) {
            $this->tenantContext->setTenant($tenantId);
        }
    }

    /**
     * Extract tenant ID from the request.
     *
     * Priority:
     * 1. X-Tenant-ID header
     * 2. Query parameter (?tenant_id=)
     */
    private function extractTenantId(\Symfony\Component\HttpFoundation\Request $request): ?string
    {
        // First try header
        $tenantId = $request->headers->get('X-Tenant-ID');
        if ($tenantId !== null && $tenantId !== '') {
            return $tenantId;
        }

        // Then try query parameter
        $tenantId = $request->query->get('tenant_id');
        if ($tenantId !== null && $tenantId !== '') {
            return $tenantId;
        }

        // Then try attribute (from route)
        $tenantId = $request->attributes->get('tenant_id');
        if ($tenantId !== null && $tenantId !== '') {
            return $tenantId;
        }

        return null;
    }
}
