<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\DTOs;

final readonly class CreateOrderRequest
{
    public function __construct(
        public string $tenantId,
        public string $customerId,
        public array $lines,
        public string $createdBy,
        public ?string $quotationId = null,
        public ?string $paymentTerms = null,
        public ?string $shippingAddress = null,
        public ?string $billingAddress = null,
        public ?string $salespersonId = null,
        public array $metadata = []
    ) {}
}

final readonly class OrderLineRequest
{
    public function __construct(
        public string $productVariantId,
        public float $quantity,
        public float $unitPrice,
        public float $discountPercent = 0.0,
        public ?string $uomCode = null,
        public ?string $description = null
    ) {}
}

final readonly class CreateOrderResult
{
    public function __construct(
        public bool $success,
        public ?string $orderId = null,
        public ?string $orderNumber = null,
        public ?string $status = null,
        public ?string $message = null,
        public array $issues = []
    ) {}
}
