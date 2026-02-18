<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Services;

final readonly class RevenueRecognitionInput
{
    public function __construct(
        public string $orderId,
        public float $totalAmount,
        public string $currencyCode,
        public array $lines = [],
        public ?\DateTimeImmutable $orderDate = null,
        public ?string $contractType = null,
    ) {}
}
