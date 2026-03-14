<?php

declare(strict_types=1);

namespace Nexus\Milestone\Contracts;

use Nexus\Milestone\ValueObjects\MilestoneSummary;

/**
 * Read-only milestone query contract.
 */
interface MilestoneQueryInterface
{
    public function getById(string $milestoneId): ?MilestoneSummary;

    /** @return list<MilestoneSummary> */
    public function getByContext(string $contextId): array;
}
