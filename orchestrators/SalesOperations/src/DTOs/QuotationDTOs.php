<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\DTOs;

final readonly class ConvertQuotationRequest
{
    public function __construct(
        public string $tenantId,
        public string $quotationId,
        public string $convertedBy,
        public ?string $notes = null,
        public array $overrides = []
    ) {}
}

final readonly class ConvertQuotationResult
{
    public function __construct(
        public bool $success,
        public ?string $orderId = null,
        public ?string $orderNumber = null,
        public ?string $message = null,
        public array $issues = []
    ) {}
}
