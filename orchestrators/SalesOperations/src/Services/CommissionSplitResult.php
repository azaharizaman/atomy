<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Services;

final readonly class CommissionSplitResult
{
    public function __construct(
        public string $salespersonId,
        public float $splitPercent,
        public float $commissionAmount,
        public string $currencyCode,
    ) {}
}
