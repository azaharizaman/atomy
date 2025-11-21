<?php

declare(strict_types=1);

namespace App\Services\Sales;

use Nexus\Receivable\Contracts\CustomerInvoiceInterface;
use Nexus\Receivable\Contracts\ReceivableManagerInterface;
use Nexus\Sales\Contracts\InvoiceManagerInterface;

/**
 * Receivable Invoice Adapter
 *
 * Implements Sales package InvoiceManagerInterface by delegating to ReceivableManager.
 * Replaces StubInvoiceManager.
 */
final readonly class ReceivableInvoiceAdapter implements InvoiceManagerInterface
{
    public function __construct(
        private ReceivableManagerInterface $receivableManager
    ) {}

    public function createInvoiceFromOrder(string $salesOrderId): CustomerInvoiceInterface
    {
        return $this->receivableManager->createInvoiceFromOrder($salesOrderId);
    }

    public function getById(string $invoiceId): CustomerInvoiceInterface
    {
        return $this->receivableManager->getById($invoiceId);
    }

    public function getByNumber(string $tenantId, string $invoiceNumber): ?CustomerInvoiceInterface
    {
        return $this->receivableManager->getByNumber($tenantId, $invoiceNumber);
    }

    public function postInvoice(string $invoiceId): void
    {
        $this->receivableManager->postInvoiceToGL($invoiceId);
    }

    public function voidInvoice(string $invoiceId, string $reason): void
    {
        $this->receivableManager->voidInvoice($invoiceId, $reason);
    }
}
