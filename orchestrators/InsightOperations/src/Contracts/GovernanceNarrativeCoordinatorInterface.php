<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

use Nexus\InsightOperations\DTOs\InsightResultDto;

interface GovernanceNarrativeCoordinatorInterface
{
    public function show(string $tenantId, string $vendorId): InsightResultDto;

    public function generate(string $tenantId, string $vendorId, string $actorId): InsightResultDto;
}
