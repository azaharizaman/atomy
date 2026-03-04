<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Contracts;

use Nexus\DataExchangeOperations\DTOs\DataOffboardingRequest;

interface DataExportPortInterface
{
    /**
     * @return array{source_path:string,format:string,size_bytes:int,metadata:array<string,mixed>}
     */
    public function export(DataOffboardingRequest $request): array;
}
