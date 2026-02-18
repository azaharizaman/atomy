<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Services;

final readonly class CommissionInput
{
    public function __construct(
        public string $salespersonId,
        public string $orderId,
        public string $paymentId,
        public float $revenueAmount,
        public float $costAmount,
        public string $currencyCode = 'MYR',
        public ?string $basis = null,
        public ?float $customRate = null,
        public ?float $overrideRate = null,
        public ?float $ytdRevenue = null,
        public ?float $overheadAmount = null,
    ) {}
}
