<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Contracts;

use Nexus\AccountConsolidation\ValueObjects\ConsolidationResult;

/**
 * Contract for the consolidation engine.
 */
interface ConsolidationEngineInterface
{
    /**
     * Perform consolidation for a group of entities.
     *
     * @param array<string> $entityIds Entity IDs to consolidate
     * @param \DateTimeImmutable $asOfDate Consolidation date
     * @param array<string, mixed> $options Consolidation options
     * @return ConsolidationResult
     */
    public function consolidate(
        array $entityIds,
        \DateTimeImmutable $asOfDate,
        array $options = []
    ): ConsolidationResult;
}
