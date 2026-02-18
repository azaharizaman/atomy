<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Services;

final readonly class DeferredRevenueResult
{
    public function __construct(
        public string $contractId,
        public float $totalContractValue,
        public float $totalRecognized,
        public float $deferredRevenue,
        public float $recognitionPercent,
    ) {}
}
