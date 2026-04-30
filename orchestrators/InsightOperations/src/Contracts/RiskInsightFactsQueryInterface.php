<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

use Nexus\InsightOperations\DTOs\RiskInsightFactsDto;

interface RiskInsightFactsQueryInterface
{
    public function factsForRfq(string $tenantId, string $rfqId): RiskInsightFactsDto;
}
