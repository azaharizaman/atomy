<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Orchestrator-facing contract to load subledger-to-GL mappings.
 */
interface GLMappingRepositoryInterface
{
    /**
     * Return mapping entries for a given subledger type.
     *
     * @return array<object>
     */
    public function getMappingsForSubledger(string $tenantId, string $subledgerType): array;
}
