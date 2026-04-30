<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

interface RiskInsightFactsCommandInterface
{
    public function escalate(string $tenantId, string $rfqId, string $itemId): void;

    public function resolveAsException(string $tenantId, string $rfqId, string $itemId, string $actorId): void;
}
