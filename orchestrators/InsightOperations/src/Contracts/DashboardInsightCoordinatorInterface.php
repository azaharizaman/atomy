<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

use Nexus\InsightOperations\DTOs\InsightResultDto;

interface DashboardInsightCoordinatorInterface
{
    public function show(string $tenantId): InsightResultDto;

    public function generate(string $tenantId, string $actorId): InsightResultDto;
}
