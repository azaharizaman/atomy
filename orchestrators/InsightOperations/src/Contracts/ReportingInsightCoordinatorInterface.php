<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

use Nexus\InsightOperations\DTOs\InsightResultDto;

interface ReportingInsightCoordinatorInterface
{
    public function show(
        string $tenantId,
        string $subjectType,
    ): InsightResultDto;

    public function generate(
        string $tenantId,
        string $subjectType,
        string $actorId,
    ): InsightResultDto;
}
