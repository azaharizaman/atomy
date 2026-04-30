<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

use Nexus\InsightOperations\DTOs\InsightResultDto;

interface RiskInsightCoordinatorInterface
{
    public function show(string $tenantId, string $rfqId): InsightResultDto;

    public function generate(string $tenantId, string $rfqId, string $actorId): InsightResultDto;

    public function escalate(string $tenantId, string $rfqId, string $itemId): void;

    public function resolveAsException(string $tenantId, string $rfqId, string $itemId, string $actorId): void;
}
