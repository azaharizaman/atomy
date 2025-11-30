<?php

declare(strict_types=1);

namespace Nexus\Payroll\Contracts;

/**
 * Query contract for payroll component read operations.
 *
 * Implements CQRS pattern - read operations only.
 */
interface ComponentQueryInterface
{
    /**
     * Find a component by its ID.
     *
     * @param string $id Component ULID
     * @return ComponentInterface|null
     */
    public function findById(string $id): ?ComponentInterface;

    /**
     * Find a component by its code within a tenant.
     *
     * @param string $tenantId Tenant ULID
     * @param string $code Component code
     * @return ComponentInterface|null
     */
    public function findByCode(string $tenantId, string $code): ?ComponentInterface;

    /**
     * Get all active components for a tenant.
     *
     * @param string $tenantId Tenant ULID
     * @param string|null $type Optional filter by type ('earning' or 'deduction')
     * @return array<ComponentInterface>
     */
    public function getActiveComponents(string $tenantId, ?string $type = null): array;
}
