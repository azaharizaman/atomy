<?php

declare(strict_types=1);

namespace Nexus\Laravel\InsightOperations\Adapters;

use Nexus\Export\Contracts\ExportGeneratorInterface;
use Nexus\InsightOperations\Contracts\ReportExportPortInterface;

final readonly class ReportExportPortAdapter implements ReportExportPortInterface
{
    public function __construct(private ExportGeneratorInterface $exportGenerator) {}

    public function export(array $reportData, string $format): array
    {
        $result = $this->exportGenerator->generate($reportData, $format);

        return [
            'file_path' => $result->getFilePathOrFail(),
            'size_bytes' => $result->sizeBytes,
            'metadata' => $result->metadata,
        ];
    }
}
