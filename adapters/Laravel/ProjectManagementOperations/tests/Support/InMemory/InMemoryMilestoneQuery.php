<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Tests\Support\InMemory;

use Nexus\Milestone\Contracts\MilestoneQueryInterface;
use Nexus\Milestone\ValueObjects\MilestoneSummary;
use Nexus\Milestone\Enums\MilestoneStatus;

/**
 * In-memory L1 MilestoneQueryInterface for integration tests.
 */
final class InMemoryMilestoneQuery implements MilestoneQueryInterface
{
    /** @var array<string, MilestoneSummary> */
    private array $byId = [];
    /** @var array<string, list<MilestoneSummary>> */
    private array $byContext = [];

    public function add(MilestoneSummary $milestone): void
    {
        $this->byId[$milestone->id] = $milestone;
        $this->byContext[$milestone->contextId] = $this->byContext[$milestone->contextId] ?? [];
        $this->byContext[$milestone->contextId][] = $milestone;
    }

    public function getById(string $milestoneId): ?MilestoneSummary
    {
        return $this->byId[$milestoneId] ?? null;
    }

    /** @return list<MilestoneSummary> */
    public function getByContext(string $contextId): array
    {
        return $this->byContext[$contextId] ?? [];
    }
}
