<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

/**
 * Query interface for retrieving financial statements.
 *
 * Follows CQRS pattern - read operations only.
 */
interface FinancialStatementQueryInterface
{
    /**
     * Find a financial statement by its unique identifier.
     *
     * @param string $id Statement ID
     * @return FinancialStatementInterface|null
     */
    public function findById(string $id): ?FinancialStatementInterface;

    /**
     * Find statements by tenant and period.
     *
     * @param string $tenantId Tenant identifier
     * @param string $periodId Period identifier
     * @return array<FinancialStatementInterface>
     */
    public function findByTenantAndPeriod(string $tenantId, string $periodId): array;

    /**
     * Find statements by type for a tenant.
     *
     * @param string $tenantId Tenant identifier
     * @param string $type Statement type
     * @return array<FinancialStatementInterface>
     */
    public function findByType(string $tenantId, string $type): array;

    /**
     * Get all statements for a tenant.
     *
     * @param string $tenantId Tenant identifier
     * @return array<FinancialStatementInterface>
     */
    public function findAllByTenant(string $tenantId): array;

    /**
     * Find the latest statement of a specific type for a tenant.
     *
     * @param string $tenantId Tenant identifier
     * @param string $type Statement type
     * @return FinancialStatementInterface|null
     */
    public function findLatestByType(string $tenantId, string $type): ?FinancialStatementInterface;
}
