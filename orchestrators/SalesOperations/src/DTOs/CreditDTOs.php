<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\DTOs;

final readonly class CreditCheckRequest
{
    public function __construct(
        public string $tenantId,
        public string $customerId,
        public float $orderAmount,
        public string $currencyCode = 'USD',
        public ?string $orderId = null
    ) {}
}

final readonly class CreditCheckResult
{
    public function __construct(
        public bool $approved,
        public float $creditLimit,
        public float $currentUsage,
        public float $availableCredit,
        public float $requestedAmount,
        public ?string $reason = null,
        public bool $requiresManagerApproval = false
    ) {}
}
