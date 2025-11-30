<?php

declare(strict_types=1);

namespace Nexus\Tenant\Services;

use Nexus\Tenant\Contracts\EventDispatcherInterface;
use Nexus\Tenant\Contracts\TenantInterface;
use Nexus\Tenant\Contracts\TenantPersistenceInterface;
use Nexus\Tenant\Contracts\TenantQueryInterface;
use Nexus\Tenant\Contracts\TenantValidationInterface;
use Nexus\Tenant\Enums\TenantStatus;
use Nexus\Tenant\Events\TenantActivatedEvent;
use Nexus\Tenant\Events\TenantArchivedEvent;
use Nexus\Tenant\Events\TenantCreatedEvent;
use Nexus\Tenant\Events\TenantDeletedEvent;
use Nexus\Tenant\Events\TenantReactivatedEvent;
use Nexus\Tenant\Events\TenantSuspendedEvent;
use Nexus\Tenant\Events\TenantUpdatedEvent;
use Nexus\Tenant\Exceptions\DuplicateTenantCodeException;
use Nexus\Tenant\Exceptions\DuplicateTenantDomainException;
use Nexus\Tenant\Exceptions\TenantNotFoundException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Tenant Lifecycle Service
 *
 * Manages the business logic for tenant CRUD operations and lifecycle state management.
 *
 * Uses ISP-compliant split interfaces instead of fat repository.
 *
 * @package Nexus\Tenant\Services
 */
final readonly class TenantLifecycleService
{
    public function __construct(
        private TenantPersistenceInterface $persistence,
        private TenantQueryInterface $query,
        private TenantValidationInterface $validation,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * Create a new tenant.
     *
     * @param string $code
     * @param string $name
     * @param string $email
     * @param string|null $domain
     * @param array<string, mixed> $additionalData
     * @return TenantInterface
     * @throws DuplicateTenantCodeException
     * @throws DuplicateTenantDomainException
     */
    public function createTenant(
        string $code,
        string $name,
        string $email,
        ?string $domain = null,
        array $additionalData = []
    ): TenantInterface {
        // Validate uniqueness using validation interface
        if ($this->validation->codeExists($code)) {
            throw DuplicateTenantCodeException::code($code);
        }

        if ($domain && $this->validation->domainExists($domain)) {
            throw DuplicateTenantDomainException::domain($domain);
        }

        $data = array_merge([
            'code' => $code,
            'name' => $name,
            'email' => $email,
            'domain' => $domain,
            'status' => TenantStatus::Pending->value,
        ], $additionalData);

        $tenant = $this->persistence->create($data);

        $this->eventDispatcher->dispatch(new TenantCreatedEvent($tenant));
        $this->logger->info("Tenant created: {$tenant->getId()} ({$code})");

        return $tenant;
    }

    /**
     * Activate a tenant (change status from pending to active).
     *
     * @param string $tenantId
     * @return TenantInterface
     * @throws TenantNotFoundException
     */
    public function activateTenant(string $tenantId): TenantInterface
    {
        $tenant = $this->query->findById($tenantId);

        if (!$tenant) {
            throw TenantNotFoundException::byId($tenantId);
        }

        $tenant = $this->persistence->update($tenantId, [
            'status' => TenantStatus::Active->value,
        ]);

        $this->eventDispatcher->dispatch(new TenantActivatedEvent($tenant));
        $this->logger->info("Tenant activated: {$tenantId}");

        return $tenant;
    }

    /**
     * Suspend a tenant (prevent access, retain data, reversible).
     *
     * @param string $tenantId
     * @param string|null $reason
     * @return TenantInterface
     * @throws TenantNotFoundException
     */
    public function suspendTenant(string $tenantId, ?string $reason = null): TenantInterface
    {
        $tenant = $this->query->findById($tenantId);

        if (!$tenant) {
            throw TenantNotFoundException::byId($tenantId);
        }

        $data = ['status' => TenantStatus::Suspended->value];

        if ($reason) {
            $metadata = $tenant->getMetadata();
            $metadata['suspension_reason'] = $reason;
            $metadata['suspended_at'] = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
            $data['metadata'] = $metadata;
        }

        $tenant = $this->persistence->update($tenantId, $data);

        $this->eventDispatcher->dispatch(new TenantSuspendedEvent($tenant, $reason));
        $this->logger->warning("Tenant suspended: {$tenantId}" . ($reason ? " - Reason: {$reason}" : ''));

        return $tenant;
    }

    /**
     * Reactivate a suspended tenant (restore access).
     *
     * @param string $tenantId
     * @return TenantInterface
     * @throws TenantNotFoundException
     */
    public function reactivateTenant(string $tenantId): TenantInterface
    {
        $tenant = $this->query->findById($tenantId);

        if (!$tenant) {
            throw TenantNotFoundException::byId($tenantId);
        }

        $tenant = $this->persistence->update($tenantId, [
            'status' => TenantStatus::Active->value,
        ]);

        $this->eventDispatcher->dispatch(new TenantReactivatedEvent($tenant));
        $this->logger->info("Tenant reactivated: {$tenantId}");

        return $tenant;
    }

    /**
     * Archive a tenant (soft delete with retention policy).
     *
     * @param string $tenantId
     * @param string|null $reason
     * @return bool
     * @throws TenantNotFoundException
     */
    public function archiveTenant(string $tenantId, ?string $reason = null): bool
    {
        $tenant = $this->query->findById($tenantId);

        if (!$tenant) {
            throw TenantNotFoundException::byId($tenantId);
        }

        $result = $this->persistence->delete($tenantId);

        if ($result) {
            $this->eventDispatcher->dispatch(new TenantArchivedEvent($tenant, $reason));
            $this->logger->info("Tenant archived: {$tenantId}");
        }

        return $result;
    }

    /**
     * Permanently delete a tenant (hard delete after retention period).
     *
     * @param string $tenantId
     * @return bool
     */
    public function deleteTenant(string $tenantId): bool
    {
        // Note: We can't get tenant before deletion in force delete
        $result = $this->persistence->forceDelete($tenantId);

        if ($result) {
            $this->logger->warning("Tenant permanently deleted: {$tenantId}");
        }

        return $result;
    }

    /**
     * Update tenant metadata.
     *
     * @param string $tenantId
     * @param array<string, mixed> $data
     * @return TenantInterface
     * @throws TenantNotFoundException
     * @throws DuplicateTenantCodeException
     * @throws DuplicateTenantDomainException
     */
    public function updateTenant(string $tenantId, array $data): TenantInterface
    {
        $tenant = $this->query->findById($tenantId);

        if (!$tenant) {
            throw TenantNotFoundException::byId($tenantId);
        }

        // Validate code uniqueness if changing
        if (isset($data['code']) && $data['code'] !== $tenant->getCode()) {
            if ($this->validation->codeExists($data['code'], $tenantId)) {
                throw DuplicateTenantCodeException::code($data['code']);
            }
        }

        // Validate domain uniqueness if changing
        if (isset($data['domain']) && $data['domain'] !== $tenant->getDomain()) {
            if ($this->validation->domainExists($data['domain'], $tenantId)) {
                throw DuplicateTenantDomainException::domain($data['domain']);
            }
        }

        $updatedTenant = $this->persistence->update($tenantId, $data);

        $this->eventDispatcher->dispatch(new TenantUpdatedEvent($updatedTenant, []));
        $this->logger->info("Tenant updated: {$tenantId}");

        return $updatedTenant;
    }
}
