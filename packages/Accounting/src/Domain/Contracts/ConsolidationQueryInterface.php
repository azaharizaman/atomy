<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\Entities\ConsolidatedStatement;

/**
 * Query interface for consolidated financial statements.
 *
 * Follows CQRS pattern - read operations only.
 */
interface ConsolidationQueryInterface
{
    /**
     * Find a consolidated statement by ID.
     *
     * @param string $id Consolidated statement ID
     * @return ConsolidatedStatement|null
     */
    public function findById(string $id): ?ConsolidatedStatement;

    /**
     * Find consolidated statements by parent tenant and period.
     *
     * @param string $parentTenantId Parent tenant identifier
     * @param string $periodId Period identifier
     * @return array<ConsolidatedStatement>
     */
    public function findByParentAndPeriod(string $parentTenantId, string $periodId): array;

    /**
     * Get all consolidated statements for a parent tenant.
     *
     * @param string $parentTenantId Parent tenant identifier
     * @return array<ConsolidatedStatement>
     */
    public function findAllByParent(string $parentTenantId): array;

    /**
     * Find the latest consolidated statement of a type.
     *
     * @param string $parentTenantId Parent tenant identifier
     * @param string $type Statement type
     * @return ConsolidatedStatement|null
     */
    public function findLatestByType(string $parentTenantId, string $type): ?ConsolidatedStatement;
}
