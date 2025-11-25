<?php

declare(strict_types=1);

namespace Nexus\Tenant\Services;

use Nexus\Tenant\Contracts\ImpersonationStorageInterface;
use Nexus\Tenant\Contracts\EventDispatcherInterface;
use Nexus\Tenant\Contracts\TenantQueryInterface;
use Nexus\Tenant\Events\ImpersonationStartedEvent;
use Nexus\Tenant\Events\ImpersonationEndedEvent;
use Nexus\Tenant\Exceptions\TenantNotFoundException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Tenant Impersonation Service
 *
 * Manages secure impersonation of tenants by support staff.
 *
 * STATELESS: All impersonation state is externalized via ImpersonationStorageInterface.
 * No in-memory properties storing session data.
 *
 * @package Nexus\Tenant\Services
 */
final readonly class TenantImpersonationService
{
    public function __construct(
        private TenantQueryInterface $tenantQuery,
        private TenantContextManager $contextManager,
        private ImpersonationStorageInterface $storage,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * Start impersonating a tenant.
     *
     * @param string $storageKey Storage key (e.g., session ID or user ID)
     * @param string $tenantId Target tenant to impersonate
     * @param string $impersonatorId User performing impersonation
     * @param string|null $reason Optional reason for impersonation
     * @return void
     * @throws TenantNotFoundException
     */
    public function impersonate(
        string $storageKey,
        string $tenantId,
        string $impersonatorId,
        ?string $reason = null
    ): void {
        // Validate tenant exists
        $targetTenant = $this->tenantQuery->findById($tenantId);
        if (!$targetTenant) {
            throw TenantNotFoundException::byId($tenantId);
        }

        // Get current tenant context (original)
        $originalTenantId = $this->contextManager->getCurrentTenantId();
        $originalTenant = $originalTenantId ? $this->tenantQuery->findById($originalTenantId) : null;

        // Store impersonation state externally
        $this->storage->store(
            key: $storageKey,
            originalTenantId: $originalTenantId ?? 'system',
            targetTenantId: $tenantId,
            impersonatorId: $impersonatorId
        );

        // Set the tenant context
        $this->contextManager->setTenant($tenantId);

        // Dispatch event
        if ($originalTenant) {
            $this->eventDispatcher->dispatch(
                new ImpersonationStartedEvent($originalTenant, $targetTenant, $impersonatorId)
            );
        }

        $this->logger->warning(
            "Impersonation started: User {$impersonatorId} accessing tenant {$tenantId}" .
            ($reason ? " - Reason: {$reason}" : '')
        );
    }

    /**
     * Stop impersonation and restore original context.
     *
     * @param string $storageKey Storage key (e.g., session ID or user ID)
     * @return void
     */
    public function stopImpersonation(string $storageKey): void
    {
        if (!$this->storage->isActive($storageKey)) {
            return;
        }

        $context = $this->storage->retrieve($storageKey);
        if (!$context) {
            return;
        }

        $originalTenantId = $context['original_tenant_id'];
        $targetTenantId = $context['target_tenant_id'];
        $impersonatorId = $context['impersonator_id'];

        // Retrieve tenant objects for event
        $targetTenant = $this->tenantQuery->findById($targetTenantId);
        $restoredTenant = $originalTenantId !== 'system' ? $this->tenantQuery->findById($originalTenantId) : null;

        // Restore original tenant context
        if ($originalTenantId === 'system') {
            $this->contextManager->clearTenant();
        } else {
            $this->contextManager->setTenant($originalTenantId);
        }

        // Clear storage
        $this->storage->clear($storageKey);

        // Dispatch event
        if ($targetTenant && $restoredTenant) {
            $this->eventDispatcher->dispatch(
                new ImpersonationEndedEvent($targetTenant, $restoredTenant, $impersonatorId)
            );
        }

        $this->logger->info(
            "Impersonation ended: User {$impersonatorId} stopped accessing tenant {$targetTenantId}"
        );
    }

    /**
     * Check if currently impersonating a tenant.
     *
     * @param string $storageKey Storage key
     * @return bool
     */
    public function isImpersonating(string $storageKey): bool
    {
        return $this->storage->isActive($storageKey);
    }

    /**
     * Get the impersonated tenant ID.
     *
     * @param string $storageKey Storage key
     * @return string|null
     */
    public function getImpersonatedTenantId(string $storageKey): ?string
    {
        return $this->storage->getTargetTenantId($storageKey);
    }

    /**
     * Get the original tenant ID before impersonation.
     *
     * @param string $storageKey Storage key
     * @return string|null
     */
    public function getOriginalTenantId(string $storageKey): ?string
    {
        return $this->storage->getOriginalTenantId($storageKey);
    }
}
