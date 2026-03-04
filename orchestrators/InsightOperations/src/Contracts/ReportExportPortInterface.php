<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

interface ReportExportPortInterface
{
    /**
     * @param array<string, mixed> $reportData
     * @return array{file_path:string,size_bytes:int,metadata:array<string,mixed>}
     */
    public function export(array $reportData, string $format): array;
}
