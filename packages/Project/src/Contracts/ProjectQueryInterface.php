<?php

declare(strict_types=1);

namespace Nexus\Project\Contracts;

use Nexus\Project\ValueObjects\ProjectSummary;

/**
 * Read-only project query contract.
 */
interface ProjectQueryInterface
{
    public function getById(string $projectId): ?ProjectSummary;

    /**
     * Projects visible to client (BUS-PRO-0106). Filter by clientId.
     * @return list<ProjectSummary>
     */
    public function getByClient(string $clientId): array;
}
