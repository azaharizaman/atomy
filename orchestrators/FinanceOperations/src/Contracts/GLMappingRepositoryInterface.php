<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Query contract for subledger to GL account mappings.
 */
interface GLMappingRepositoryInterface
{
    /**
     * @return array<object> Mappings for the requested subledger type
     */
    public function getMappingsForSubledger(string $tenantId, string $subledgerType): array;
}
