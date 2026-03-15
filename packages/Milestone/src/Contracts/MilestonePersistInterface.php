<?php

declare(strict_types=1);

namespace Nexus\Milestone\Contracts;

use Nexus\Milestone\ValueObjects\MilestoneSummary;

/**
 * Milestone persistence (write side).
 */
interface MilestonePersistInterface
{
    public function persist(MilestoneSummary $milestone): void;
}
