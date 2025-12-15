<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Request DTO for checking approval limits.
 *
 * Contains all context needed to validate whether a user
 * can approve a document at a specific amount.
 */
final readonly class ApprovalLimitCheckRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $userId User to check approval authority for
     * @param string $documentType Type of document (requisition, purchase_order, payment, etc.)
     * @param int $amountCents Amount to approve in cents
     * @param string|null $departmentId Optional department context
     * @param string|null $documentId Optional document identifier for audit
     * @param string|null $vendorId Optional vendor identifier for vendor-specific limits
     * @param string|null $categoryCode Optional category code for category-specific limits
     */
    public function __construct(
        public string $tenantId,
        public string $userId,
        public string $documentType,
        public int $amountCents,
        public ?string $departmentId = null,
        public ?string $documentId = null,
        public ?string $vendorId = null,
        public ?string $categoryCode = null,
    ) {}

    /**
     * Create from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            userId: $data['user_id'],
            documentType: $data['document_type'],
            amountCents: $data['amount_cents'],
            departmentId: $data['department_id'] ?? null,
            documentId: $data['document_id'] ?? null,
            vendorId: $data['vendor_id'] ?? null,
            categoryCode: $data['category_code'] ?? null,
        );
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'document_type' => $this->documentType,
            'amount_cents' => $this->amountCents,
            'department_id' => $this->departmentId,
            'document_id' => $this->documentId,
            'vendor_id' => $this->vendorId,
            'category_code' => $this->categoryCode,
        ];
    }
}
