<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

interface ReportDataQueryPortInterface
{
    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function query(string $reportTemplateId, array $parameters): array;
}
