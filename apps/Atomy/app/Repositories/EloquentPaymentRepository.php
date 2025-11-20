<?php

declare(strict_types=1);

namespace Atomy\Repositories;

use Atomy\Models\Payment;
use Nexus\Payable\Contracts\PaymentRepositoryInterface;
use Nexus\Payable\Contracts\PaymentInterface;
use Illuminate\Support\Str;

/**
 * Eloquent payment repository implementation.
 */
final class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findById(string $paymentId): ?PaymentInterface
    {
        return Payment::find($paymentId);
    }

    /**
     * {@inheritdoc}
     */
    public function findByPaymentNumber(string $tenantId, string $paymentNumber): ?PaymentInterface
    {
        return Payment::where('tenant_id', $tenantId)
            ->where('payment_number', $paymentNumber)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByVendor(string $tenantId, string $vendorId, array $filters = []): array
    {
        // TODO: Implement proper vendor filtering via join with vendor_bills table.
        // Current limitation: Payment allocations are stored as JSON, making vendor filtering complex.
        // Proper implementation requires joining through vendor_bills.vendor_id.
        throw new \LogicException(
            'getByVendor is not yet implemented. Vendor filtering requires a join with ' .
            'vendor_bills table to correlate payment allocations. Use getByStatus() for now.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getByStatus(string $tenantId, string $status): array
    {
        return Payment::where('tenant_id', $tenantId)
            ->where('status', $status)
            ->orderBy('payment_date', 'desc')
            ->get()
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $tenantId, array $data): PaymentInterface
    {
        $data['id'] = Str::ulid()->toString();
        $data['tenant_id'] = $tenantId;

        return Payment::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $paymentId, array $data): PaymentInterface
    {
        $payment = Payment::findOrFail($paymentId);
        $payment->update($data);
        return $payment->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $paymentId): bool
    {
        return Payment::destroy($paymentId) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function paymentNumberExists(string $tenantId, string $paymentNumber): bool
    {
        return Payment::where('tenant_id', $tenantId)
            ->where('payment_number', $paymentNumber)
            ->exists();
    }
}
