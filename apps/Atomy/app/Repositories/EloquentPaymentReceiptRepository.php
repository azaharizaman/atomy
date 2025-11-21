<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PaymentReceipt;
use Nexus\Receivable\Contracts\PaymentReceiptInterface;
use Nexus\Receivable\Contracts\PaymentReceiptRepositoryInterface;

/**
 * Eloquent Payment Receipt Repository
 *
 * Handles persistence operations for payment receipts.
 */
final readonly class EloquentPaymentReceiptRepository implements PaymentReceiptRepositoryInterface
{
    public function findById(string $id): ?PaymentReceiptInterface
    {
        return PaymentReceipt::find($id);
    }

    public function findByNumber(string $tenantId, string $receiptNumber): ?PaymentReceiptInterface
    {
        return PaymentReceipt::where('tenant_id', $tenantId)
            ->where('receipt_number', $receiptNumber)
            ->first();
    }

    public function save(PaymentReceiptInterface $receipt): void
    {
        if (!$receipt instanceof PaymentReceipt) {
            throw new \InvalidArgumentException('Receipt must be an instance of PaymentReceipt model');
        }

        $receipt->save();
    }

    public function delete(string $id): void
    {
        PaymentReceipt::where('id', $id)->delete();
    }

    /**
     * @return PaymentReceiptInterface[]
     */
    public function getByCustomer(string $tenantId, string $customerId): array
    {
        return PaymentReceipt::where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->orderBy('receipt_date', 'desc')
            ->get()
            ->all();
    }

    /**
     * @return PaymentReceiptInterface[]
     */
    public function getUnappliedReceipts(string $tenantId, string $customerId): array
    {
        return PaymentReceipt::where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->whereIn('status', ['cleared', 'applied'])
            ->get()
            ->filter(fn($receipt) => $receipt->getUnallocatedAmount() > 0)
            ->values()
            ->all();
    }

    /**
     * @return PaymentReceiptInterface[]
     */
    public function getByStatus(string $tenantId, string $status): array
    {
        return PaymentReceipt::where('tenant_id', $tenantId)
            ->where('status', $status)
            ->orderBy('receipt_date', 'desc')
            ->get()
            ->all();
    }
}
