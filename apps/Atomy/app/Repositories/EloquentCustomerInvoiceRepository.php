<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\CustomerInvoice;
use DateTimeInterface;
use Nexus\Receivable\Contracts\CustomerInvoiceInterface;
use Nexus\Receivable\Contracts\CustomerInvoiceRepositoryInterface;
use Nexus\Receivable\Exceptions\InvoiceNotFoundException;

/**
 * Eloquent Customer Invoice Repository
 *
 * Handles persistence operations for customer invoices.
 */
final readonly class EloquentCustomerInvoiceRepository implements CustomerInvoiceRepositoryInterface
{
    public function findById(string $id): ?CustomerInvoiceInterface
    {
        return CustomerInvoice::find($id);
    }

    public function findByNumber(string $tenantId, string $invoiceNumber): ?CustomerInvoiceInterface
    {
        return CustomerInvoice::where('tenant_id', $tenantId)
            ->where('invoice_number', $invoiceNumber)
            ->first();
    }

    public function getById(string $id): CustomerInvoiceInterface
    {
        $invoice = $this->findById($id);

        if ($invoice === null) {
            throw InvoiceNotFoundException::forId($id);
        }

        return $invoice;
    }

    public function save(CustomerInvoiceInterface $invoice): void
    {
        if (!$invoice instanceof CustomerInvoice) {
            throw new \InvalidArgumentException('Invoice must be an instance of CustomerInvoice model');
        }

        $invoice->save();
    }

    public function delete(string $id): void
    {
        $invoice = $this->getById($id);

        if (!$invoice instanceof CustomerInvoice) {
            throw new \InvalidArgumentException('Invoice must be an Eloquent model');
        }

        $invoice->delete();
    }

    public function updateOutstandingBalance(string $id, float $newBalance): void
    {
        $invoice = $this->getById($id);

        if (!$invoice instanceof CustomerInvoice) {
            throw new \InvalidArgumentException('Invoice must be an Eloquent model');
        }

        $invoice->outstanding_balance = max(0.0, $newBalance);
        
        // Update status based on outstanding balance
        if ($invoice->outstanding_balance <= 0.01) {
            $invoice->status = \Nexus\Receivable\Enums\InvoiceStatus::PAID->value;
        } elseif ($invoice->outstanding_balance < $invoice->total_amount - 0.01) {
            $invoice->status = \Nexus\Receivable\Enums\InvoiceStatus::PARTIALLY_PAID->value;
        }

        $invoice->save();
    }

    /**
     * @return CustomerInvoiceInterface[]
     */
    public function getByCustomer(string $tenantId, string $customerId): array
    {
        return CustomerInvoice::where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->orderBy('invoice_date', 'desc')
            ->get()
            ->all();
    }

    /**
     * @return CustomerInvoiceInterface[]
     */
    public function getOpenInvoices(string $customerId): array
    {
        return CustomerInvoice::where('customer_id', $customerId)
            ->whereIn('status', ['posted', 'partially_paid', 'overdue'])
            ->where('outstanding_balance', '>', 0)
            ->orderBy('invoice_date', 'asc')
            ->get()
            ->all();
    }

    /**
     * @return CustomerInvoiceInterface[]
     */
    public function getOverdueInvoices(string $tenantId, DateTimeInterface $asOfDate, int $minDaysPastDue = 0): array
    {
        $query = CustomerInvoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['posted', 'partially_paid', 'overdue'])
            ->where('outstanding_balance', '>', 0)
            ->where('due_date', '<', $asOfDate->format('Y-m-d'));

        if ($minDaysPastDue > 0) {
            $deadlineDate = $asOfDate->modify("-{$minDaysPastDue} days");
            $query->where('due_date', '<=', $deadlineDate->format('Y-m-d'));
        }

        return $query->orderBy('due_date', 'asc')->get()->all();
    }

    public function getOutstandingBalance(string $customerId): float
    {
        return (float) CustomerInvoice::where('customer_id', $customerId)
            ->whereIn('status', ['posted', 'partially_paid', 'overdue'])
            ->sum('outstanding_balance');
    }

    public function getGroupOutstandingBalance(string $groupId): float
    {
        return (float) CustomerInvoice::join('parties', 'customer_invoices.customer_id', '=', 'parties.id')
            ->where('parties.customer_group_id', $groupId)
            ->whereIn('customer_invoices.status', ['posted', 'partially_paid', 'overdue'])
            ->sum('customer_invoices.outstanding_balance');
    }

    /**
     * @return CustomerInvoiceInterface[]
     */
    public function getByStatus(string $tenantId, string $status): array
    {
        return CustomerInvoice::where('tenant_id', $tenantId)
            ->where('status', $status)
            ->orderBy('invoice_date', 'desc')
            ->get()
            ->all();
    }

    /**
     * @return CustomerInvoiceInterface[]
     */
    public function getBySalesOrder(string $salesOrderId): array
    {
        return CustomerInvoice::where('sales_order_id', $salesOrderId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }
}
