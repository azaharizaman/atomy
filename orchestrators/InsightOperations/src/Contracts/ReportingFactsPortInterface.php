<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

use Nexus\InsightOperations\DTOs\ReportingFactsDto;

interface ReportingFactsPortInterface
{
    public function factsForTenant(string $tenantId, string $subjectType): ReportingFactsDto;
}
