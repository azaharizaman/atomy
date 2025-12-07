<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Result DTO for purchase order operations.
 */
final readonly class PurchaseOrderResult
{
    /**
     * @param bool $success Whether the operation succeeded
     * @param string|null $purchaseOrderId Created/updated PO ID
     * @param string|null $purchaseOrderNumber Human-readable PO number
     * @param string|null $status Current PO status
     * @param string|null $message Human-readable result message
     * @param int|null $totalAmountCents Total PO amount in cents
     * @param string|null $vendorId Vendor ID
     * @param string|null $vendorName Vendor display name
     * @param \DateTimeImmutable|null $sentAt When PO was sent to vendor
     * @param int|null $amendmentNumber Amendment version number
     * @param array<string, mixed>|null $issues Validation issues or errors
     */
    public function __construct(
        public bool $success,
        public ?string $purchaseOrderId = null,
        public ?string $purchaseOrderNumber = null,
        public ?string $status = null,
        public ?string $message = null,
        public ?int $totalAmountCents = null,
        public ?string $vendorId = null,
        public ?string $vendorName = null,
        public ?\DateTimeImmutable $sentAt = null,
        public ?int $amendmentNumber = null,
        public ?array $issues = null,
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(
        string $purchaseOrderId,
        string $purchaseOrderNumber,
        string $status,
        int $totalAmountCents,
        string $vendorId,
        ?string $vendorName = null,
        ?string $message = null
    ): self {
        return new self(
            success: true,
            purchaseOrderId: $purchaseOrderId,
            purchaseOrderNumber: $purchaseOrderNumber,
            status: $status,
            message: $message,
            totalAmountCents: $totalAmountCents,
            vendorId: $vendorId,
            vendorName: $vendorName,
        );
    }

    /**
     * Create a failure result.
     *
     * @param array<string, mixed>|null $issues
     */
    public static function failure(string $message, ?array $issues = null): self
    {
        return new self(
            success: false,
            message: $message,
            issues: $issues,
        );
    }
}
