<?php

declare(strict_types=1);

namespace Nexus\Laravel\DataExchangeOperations\Adapters;

use Nexus\DataExchangeOperations\Contracts\DataExportPortInterface;
use Nexus\DataExchangeOperations\DTOs\DataOffboardingRequest;
use Nexus\Export\Contracts\ExportGeneratorInterface;

final readonly class ExportPortAdapter implements DataExportPortInterface
{
    public function __construct(private ExportGeneratorInterface $exportGenerator) {}

    public function export(DataOffboardingRequest $request): array
    {
        $result = $this->exportGenerator->generate($request->query, $request->format);

        return [
            'source_path' => $result->getFilePathOrFail(),
            'format' => $request->format,
            'size_bytes' => $result->sizeBytes,
            'metadata' => $result->metadata,
        ];
    }
}
