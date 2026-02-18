<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\DataProviders;

final readonly class InvoiceContext
{
    public function __construct(
        public string $orderId,
        public string $orderNumber,
        public string $tenantId,
        public string $customerId,
        public string $orderStatus,
        public float $orderTotal,
        public string $currencyCode,
        public string $paymentTerms,
        public array $lines,
        public ?array $invoice,
        public array $invoiceStatus,
    ) {}

    public function hasInvoice(): bool
    {
        return $this->invoice !== null;
    }

    public function isFullyInvoiced(): bool
    {
        return $this->invoiceStatus['is_fully_invoiced'];
    }

    public function isPaid(): bool
    {
        return $this->invoiceStatus['is_paid'];
    }

    public function hasBalanceDue(): bool
    {
        return $this->invoiceStatus['balance_due'] > 0;
    }

    public function canCreateInvoice(): bool
    {
        return !$this->isFullyInvoiced() && $this->invoiceStatus['remaining_quantity'] > 0;
    }

    public function getLinesToInvoice(): array
    {
        return array_filter(
            $this->lines,
            fn($line) => $line['remaining_to_invoice'] > 0
        );
    }
}
