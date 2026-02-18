<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Events;

final readonly class CommissionCalculatedEvent
{
    public function __construct(
        public string $tenantId,
        public string $commissionId,
        public string $salespersonId,
        public string $orderId,
        public string $paymentId,
        public float $commissionAmount,
        public string $currencyCode,
        public float $rate,
        public string $basis,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
