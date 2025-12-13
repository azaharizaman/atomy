<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Payment batch for a specific entity.
 */
final readonly class MultiEntityPaymentBatch
{
    public function __construct(
        public string $batchId,
        public string $entityId,
        public string $entityName,
        public string $bankId,
        public string $bankAccountNumber,
        public Money $totalAmount,
        public string $currency,
        public string $paymentMethod, // ACH, WIRE, CHECK
        /** @var array<PaymentItemData> Individual payment items */
        public array $paymentItems,
        public \DateTimeImmutable $executionDate,
        public ?string $approvedBy = null,
        public ?\DateTimeImmutable $approvedAt = null,
        public array $metadata = [],
    ) {}

    /**
     * Get count of payments in batch.
     */
    public function getPaymentCount(): int
    {
        return count($this->paymentItems);
    }

    /**
     * Check if batch is approved.
     */
    public function isApproved(): bool
    {
        return $this->approvedBy !== null && $this->approvedAt !== null;
    }

    /**
     * Get unique vendors in batch.
     */
    public function getUniqueVendorCount(): int
    {
        $vendorIds = array_map(
            fn(PaymentItemData $item) => $item->vendorId,
            $this->paymentItems,
        );

        return count(array_unique($vendorIds));
    }

    /**
     * Get payments by vendor.
     *
     * @return array<string, array<PaymentItemData>>
     */
    public function getPaymentsByVendor(): array
    {
        $byVendor = [];
        foreach ($this->paymentItems as $item) {
            $byVendor[$item->vendorId][] = $item;
        }
        return $byVendor;
    }
}
