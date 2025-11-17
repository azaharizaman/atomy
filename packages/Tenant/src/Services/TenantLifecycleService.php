<?php

declare(strict_types=1);

namespace Nexus\Tenant\Services;

use Nexus\Tenant\Contracts\TenantInterface;
use Nexus\Tenant\Contracts\TenantRepositoryInterface;
use Nexus\Tenant\Exceptions\DuplicateTenantCodeException;
use Nexus\Tenant\Exceptions\DuplicateTenantDomainException;
use Nexus\Tenant\Exceptions\TenantNotFoundException;
use Nexus\Tenant\ValueObjects\TenantStatus;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Tenant Lifecycle Service
 *
 * Manages the business logic for tenant CRUD operations and lifecycle state management.
 *
 * @package Nexus\Tenant\Services
 */
class TenantLifecycleService
{
    public function __construct(
        private readonly TenantRepositoryInterface $repository,
        private readonly TenantEventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger = new NullLogger()
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
        // Validate uniqueness
        if ($this->repository->codeExists($code)) {
            throw DuplicateTenantCodeException::code($code);
        }

        if ($domain && $this->repository->domainExists($domain)) {
            throw DuplicateTenantDomainException::domain($domain);
        }

        $data = array_merge([
            'code' => $code,
            'name' => $name,
            'email' => $email,
            'domain' => $domain,
            'status' => TenantStatus::Pending->value,
        ], $additionalData);

        $tenant = $this->repository->create($data);

        $this->eventDispatcher->dispatchTenantCreated($tenant);
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
        $tenant = $this->repository->findById($tenantId);

        if (!$tenant) {
            throw TenantNotFoundException::byId($tenantId);
        }

        $tenant = $this->repository->update($tenantId, [
            'status' => TenantStatus::Active->value,
        ]);

        $this->eventDispatcher->dispatchTenantActivated($tenant);
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
        $tenant = $this->repository->findById($tenantId);

        if (!$tenant) {
            throw TenantNotFoundException::byId($tenantId);
        }

        $data = ['status' => TenantStatus::Suspended->value];

        if ($reason) {
            $metadata = $tenant->getMetadata();
            $metadata['suspension_reason'] = $reason;
            $metadata['suspended_at'] = date('Y-m-d H:i:s');
            $data['metadata'] = $metadata;
        }

        $tenant = $this->repository->update($tenantId, $data);

        $this->eventDispatcher->dispatchTenantSuspended($tenant, $reason);
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
        $tenant = $this->repository->findById($tenantId);

        if (!$tenant) {
            throw TenantNotFoundException::byId($tenantId);
        }

        $tenant = $this->repository->update($tenantId, [
            'status' => TenantStatus::Active->value,
        ]);

        $this->eventDispatcher->dispatchTenantReactivated($tenant);
        $this->logger->info("Tenant reactivated: {$tenantId}");

        return $tenant;
    }

    /**
     * Archive a tenant (soft delete with retention policy).
     *
     * @param string $tenantId
     * @return bool
     * @throws TenantNotFoundException
     */
    public function archiveTenant(string $tenantId): bool
    {
        $tenant = $this->repository->findById($tenantId);

        if (!$tenant) {
            throw TenantNotFoundException::byId($tenantId);
        }

        $result = $this->repository->delete($tenantId);

        if ($result) {
            $this->eventDispatcher->dispatchTenantArchived($tenant);
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
        $result = $this->repository->forceDelete($tenantId);

        if ($result) {
            $this->eventDispatcher->dispatchTenantDeleted($tenantId);
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
     */
    public function updateTenant(string $tenantId, array $data): TenantInterface
    {
        $tenant = $this->repository->findById($tenantId);

        if (!$tenant) {
            throw TenantNotFoundException::byId($tenantId);
        }

        // Validate code uniqueness if changing
        if (isset($data['code']) && $data['code'] !== $tenant->getCode()) {
            if ($this->repository->codeExists($data['code'], $tenantId)) {
                throw DuplicateTenantCodeException::code($data['code']);
            }
        }

        // Validate domain uniqueness if changing
        if (isset($data['domain']) && $data['domain'] !== $tenant->getDomain()) {
            if ($this->repository->domainExists($data['domain'], $tenantId)) {
                throw DuplicateTenantDomainException::domain($data['domain']);
            }
        }

        $tenant = $this->repository->update($tenantId, $data);

        $this->eventDispatcher->dispatchTenantUpdated($tenant);
        $this->logger->info("Tenant updated: {$tenantId}");

        return $tenant;
    }
}
