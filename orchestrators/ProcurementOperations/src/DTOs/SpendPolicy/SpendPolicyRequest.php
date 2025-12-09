<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\SpendPolicy;

use Nexus\Common\ValueObjects\Money;

/**
 * Request DTO for spend policy evaluation.
 */
final readonly class SpendPolicyRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $documentType Document type (requisition, po, invoice)
     * @param string $documentId Document identifier
     * @param Money $amount Total amount of the transaction
     * @param string $categoryId Procurement category identifier
     * @param string|null $vendorId Vendor identifier
     * @param string|null $departmentId Department identifier
     * @param string|null $costCenterId Cost center identifier
     * @param string|null $projectId Project identifier
     * @param string $requestorId User requesting the transaction
     * @param \DateTimeImmutable $transactionDate Date of the transaction
     * @param array<string, mixed> $metadata Additional context data
     */
    public function __construct(
        public string $tenantId,
        public string $documentType,
        public string $documentId,
        public Money $amount,
        public string $categoryId,
        public ?string $vendorId = null,
        public ?string $departmentId = null,
        public ?string $costCenterId = null,
        public ?string $projectId = null,
        public string $requestorId = '',
        public ?\DateTimeImmutable $transactionDate = null,
        public array $metadata = [],
    ) {}

    /**
     * Get the transaction date or current time.
     */
    public function getTransactionDate(): \DateTimeImmutable
    {
        return $this->transactionDate ?? new \DateTimeImmutable();
    }

    /**
     * Check if this request has vendor context.
     */
    public function hasVendor(): bool
    {
        return $this->vendorId !== null && $this->vendorId !== '';
    }

    /**
     * Check if this request has department context.
     */
    public function hasDepartment(): bool
    {
        return $this->departmentId !== null && $this->departmentId !== '';
    }

    /**
     * Get metadata value by key.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
}
