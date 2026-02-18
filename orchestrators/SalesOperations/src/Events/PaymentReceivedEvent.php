<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Events;

final readonly class PaymentReceivedEvent
{
    public function __construct(
        public string $tenantId,
        public string $paymentId,
        public string $invoiceId,
        public string $orderId,
        public string $customerId,
        public float $amount,
        public string $currencyCode,
        public string $paymentMethod,
        public ?string $salespersonId = null,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
