<?php

declare(strict_types=1);

namespace Atomy\Repositories;

use Atomy\Models\PaymentSchedule;
use Nexus\Payable\Contracts\PaymentScheduleRepositoryInterface;
use Nexus\Payable\Contracts\PaymentScheduleInterface;
use Illuminate\Support\Str;

/**
 * Eloquent payment schedule repository implementation.
 */
final class EloquentPaymentScheduleRepository implements PaymentScheduleRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findById(string $scheduleId): ?PaymentScheduleInterface
    {
        return PaymentSchedule::find($scheduleId);
    }

    /**
     * {@inheritdoc}
     */
    public function findByBillId(string $billId): ?PaymentScheduleInterface
    {
        return PaymentSchedule::where('bill_id', $billId)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByVendor(string $tenantId, string $vendorId, array $filters = []): array
    {
        $query = PaymentSchedule::where('tenant_id', $tenantId)
            ->where('vendor_id', $vendorId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->where('due_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('due_date', '<=', $filters['to_date']);
        }

        return $query->orderBy('due_date')->get()->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getDueByDate(string $tenantId, \DateTimeInterface $asOfDate): array
    {
        return PaymentSchedule::where('tenant_id', $tenantId)
            ->where('due_date', '<=', $asOfDate->format('Y-m-d'))
            ->where('status', 'scheduled')
            ->orderBy('due_date')
            ->get()
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getOverdue(string $tenantId, \DateTimeInterface $asOfDate): array
    {
        return PaymentSchedule::where('tenant_id', $tenantId)
            ->where('due_date', '<', $asOfDate->format('Y-m-d'))
            ->where('status', 'scheduled')
            ->orderBy('due_date')
            ->get()
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $tenantId, array $data): PaymentScheduleInterface
    {
        $data['id'] = Str::ulid()->toString();
        $data['tenant_id'] = $tenantId;

        return PaymentSchedule::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $scheduleId, array $data): PaymentScheduleInterface
    {
        $schedule = PaymentSchedule::findOrFail($scheduleId);
        $schedule->update($data);
        return $schedule->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $scheduleId): bool
    {
        return PaymentSchedule::destroy($scheduleId) > 0;
    }
}
