<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

use Nexus\InsightOperations\DTOs\DashboardFactsDto;

interface DashboardFactsPortInterface
{
    public function factsForTenant(string $tenantId): DashboardFactsDto;
}
