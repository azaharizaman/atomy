<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\DTOs;

final readonly class FulfillmentRequest
{
    public function __construct(
        public string $tenantId,
        public string $orderId,
        public string $warehouseId,
        public array $lines,
        public string $shippedBy,
        public ?string $trackingNumber = null,
        public ?string $carrierCode = null,
        public array $metadata = []
    ) {}
}

final readonly class FulfillmentLineRequest
{
    public function __construct(
        public string $productVariantId,
        public float $quantity,
        public ?string $orderLineId = null
    ) {}
}

final readonly class FulfillmentResult
{
    public function __construct(
        public bool $success,
        public ?string $shipmentId = null,
        public ?string $invoiceId = null,
        public ?string $message = null,
        public array $issues = []
    ) {}
}
