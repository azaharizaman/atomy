<?php

declare(strict_types=1);

namespace Nexus\Tenant\Services;

use Nexus\Tenant\Contracts\TenantRepositoryInterface;
use Nexus\Tenant\Exceptions\ImpersonationNotAllowedException;
use Nexus\Tenant\Exceptions\TenantNotFoundException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Tenant Impersonation Service
 *
 * Manages secure impersonation of tenants by support staff.
 *
 * @package Nexus\Tenant\Services
 */
class TenantImpersonationService
{
    private ?string $originalUserId = null;
    private ?string $impersonatedTenantId = null;
    private ?string $impersonationReason = null;
    private ?\DateTimeInterface $impersonationStartedAt = null;

    public function __construct(
        private readonly TenantRepositoryInterface $repository,
        private readonly TenantContextManager $contextManager,
        private readonly TenantEventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * Start impersonating a tenant.
     *
     * @param string $tenantId
     * @param string $originalUserId
     * @param string|null $reason
     * @return void
     * @throws TenantNotFoundException
     * @throws ImpersonationNotAllowedException
     */
    public function impersonate(string $tenantId, string $originalUserId, ?string $reason = null): void
    {
        // Validate tenant exists
        $tenant = $this->repository->findById($tenantId);
        if (!$tenant) {
            throw TenantNotFoundException::byId($tenantId);
        }

        // Note: Permission validation should be done by the application layer
        // before calling this method, but we track it here for audit purposes

        $this->originalUserId = $originalUserId;
        $this->impersonatedTenantId = $tenantId;
        $this->impersonationReason = $reason;
        $this->impersonationStartedAt = new \DateTimeImmutable();

        // Set the tenant context
        $this->contextManager->setTenant($tenantId);

        $this->eventDispatcher->dispatchImpersonationStarted($tenantId, $originalUserId, $reason);
        $this->logger->warning("Impersonation started: User {$originalUserId} accessing tenant {$tenantId}" . ($reason ? " - Reason: {$reason}" : ''));
    }

    /**
     * Stop impersonation and restore original context.
     *
     * @return void
     */
    public function stopImpersonation(): void
    {
        if (!$this->isImpersonating()) {
            return;
        }

        $tenantId = $this->impersonatedTenantId;
        $originalUserId = $this->originalUserId;
        $duration = $this->getImpersonationDuration();

        $this->contextManager->clearTenant();

        $this->eventDispatcher->dispatchImpersonationEnded($tenantId, $originalUserId);
        $this->logger->info("Impersonation ended: User {$originalUserId} stopped accessing tenant {$tenantId}. Duration: {$duration} seconds");

        $this->originalUserId = null;
        $this->impersonatedTenantId = null;
        $this->impersonationReason = null;
        $this->impersonationStartedAt = null;
    }

    /**
     * Check if currently impersonating a tenant.
     *
     * @return bool
     */
    public function isImpersonating(): bool
    {
        return $this->impersonatedTenantId !== null;
    }

    /**
     * Get the impersonated tenant ID.
     *
     * @return string|null
     */
    public function getImpersonatedTenantId(): ?string
    {
        return $this->impersonatedTenantId;
    }

    /**
     * Get the original user ID who initiated impersonation.
     *
     * @return string|null
     */
    public function getOriginalUserId(): ?string
    {
        return $this->originalUserId;
    }

    /**
     * Get the impersonation reason.
     *
     * @return string|null
     */
    public function getImpersonationReason(): ?string
    {
        return $this->impersonationReason;
    }

    /**
     * Get when impersonation started.
     *
     * @return \DateTimeInterface|null
     */
    public function getImpersonationStartedAt(): ?\DateTimeInterface
    {
        return $this->impersonationStartedAt;
    }

    /**
     * Get impersonation duration in seconds.
     *
     * @return int
     */
    public function getImpersonationDuration(): int
    {
        if (!$this->impersonationStartedAt) {
            return 0;
        }

        return (new \DateTimeImmutable())->getTimestamp() - $this->impersonationStartedAt->getTimestamp();
    }
}
