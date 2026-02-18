<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Workflows\Rma;

final readonly class RmaResult
{
    public function __construct(
        public string $rmaId,
        public string $salesOrderId,
        public RmaStatus $status,
        public array $items,
        public ?array $metadata = null,
        public ?\DateTimeImmutable $createdAt = null,
        public ?\DateTimeImmutable $updatedAt = null,
        private ?string $tenantId = null,
        private ?string $customerId = null
    ) {
    }

    public function getTenantId(): string
    {
        return $this->tenantId ?? ($this->items[0]['tenant_id'] ?? '');
    }

    public function getCustomerId(): string
    {
        return $this->customerId ?? ($this->items[0]['customer_id'] ?? '');
    }

    public function withStatus(RmaStatus $status, ?array $metadata = null): self
    {
        return new self(
            rmaId: $this->rmaId,
            salesOrderId: $this->salesOrderId,
            status: $status,
            items: $this->items,
            metadata: $metadata ?? $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
            tenantId: $this->tenantId,
            customerId: $this->customerId
        );
    }
}
