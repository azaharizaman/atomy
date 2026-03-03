<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Contracts;

use Nexus\DataExchangeOperations\DTOs\DataOnboardingRequest;

interface DataImportPortInterface
{
    /**
     * @return array{records_processed:int,records_failed:int,warnings:array<int,string>,details:array<string,mixed>}
     */
    public function import(DataOnboardingRequest $request): array;
}
