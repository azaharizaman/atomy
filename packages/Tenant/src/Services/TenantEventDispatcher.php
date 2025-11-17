<?php

declare(strict_types=1);

namespace Nexus\Tenant\Services;

use Nexus\Tenant\Contracts\TenantInterface;

/**
 * Tenant Event Dispatcher
 *
 * Dispatches framework-agnostic events for tenant lifecycle changes.
 * The application layer should listen to these events and handle them appropriately.
 *
 * @package Nexus\Tenant\Services
 */
class TenantEventDispatcher
{
    private array $listeners = [];

    /**
     * Register an event listener.
     *
     * @param string $event
     * @param callable $listener
     * @return void
     */
    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    /**
     * Dispatch an event to all registered listeners.
     *
     * @param string $event
     * @param mixed ...$args
     * @return void
     */
    private function dispatch(string $event, mixed ...$args): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }

        foreach ($this->listeners[$event] as $listener) {
            $listener(...$args);
        }
    }

    public function dispatchTenantCreated(TenantInterface $tenant): void
    {
        $this->dispatch('tenant.created', $tenant);
    }

    public function dispatchTenantActivated(TenantInterface $tenant): void
    {
        $this->dispatch('tenant.activated', $tenant);
    }

    public function dispatchTenantSuspended(TenantInterface $tenant, ?string $reason = null): void
    {
        $this->dispatch('tenant.suspended', $tenant, $reason);
    }

    public function dispatchTenantReactivated(TenantInterface $tenant): void
    {
        $this->dispatch('tenant.reactivated', $tenant);
    }

    public function dispatchTenantArchived(TenantInterface $tenant): void
    {
        $this->dispatch('tenant.archived', $tenant);
    }

    public function dispatchTenantDeleted(string $tenantId): void
    {
        $this->dispatch('tenant.deleted', $tenantId);
    }

    public function dispatchTenantUpdated(TenantInterface $tenant): void
    {
        $this->dispatch('tenant.updated', $tenant);
    }

    public function dispatchImpersonationStarted(string $tenantId, string $originalUserId, ?string $reason = null): void
    {
        $this->dispatch('tenant.impersonation.started', $tenantId, $originalUserId, $reason);
    }

    public function dispatchImpersonationEnded(string $tenantId, string $originalUserId): void
    {
        $this->dispatch('tenant.impersonation.ended', $tenantId, $originalUserId);
    }
}
