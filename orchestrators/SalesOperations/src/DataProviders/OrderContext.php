<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\DataProviders;

final readonly class OrderContext
{
    public function __construct(
        public string $orderId,
        public string $orderNumber,
        public string $tenantId,
        public string $customerId,
        public string $customerName,
        public ?string $customerCode,
        public string $status,
        public float $subtotal,
        public float $taxAmount,
        public float $total,
        public string $currencyCode,
        public string $paymentTerms,
        public ?string $shippingAddress,
        public ?string $billingAddress,
        public ?string $salespersonId,
        public ?\DateTimeImmutable $confirmedAt,
        public array $lines,
        public array $credit,
        public array $stock,
    ) {}

    public function isConfirmed(): bool
    {
        return $this->confirmedAt !== null;
    }

    public function hasAvailableCredit(): bool
    {
        return $this->credit['available'] > 0;
    }

    public function canShip(): bool
    {
        foreach ($this->lines as $line) {
            if ($line['remaining_to_ship'] > 0) {
                return true;
            }
        }
        return false;
    }

    public function canInvoice(): bool
    {
        foreach ($this->lines as $line) {
            if ($line['remaining_to_invoice'] > 0) {
                return true;
            }
        }
        return false;
    }
}
