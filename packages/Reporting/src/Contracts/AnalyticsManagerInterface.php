<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

interface AnalyticsManagerInterface
{
    /** @param array<string, mixed> $parameters */
    public function runQuery(string $queryId, string $dataset = '', string $projection = '', array $parameters = []): QueryResultInterface;
}
