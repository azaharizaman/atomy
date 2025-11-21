<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ReceivableSchedule;
use Nexus\Receivable\Contracts\ReceivableScheduleInterface;
use Nexus\Receivable\Contracts\ReceivableScheduleRepositoryInterface;
use Nexus\Receivable\Exceptions\ReceivableScheduleNotFoundException;

/**
 * Eloquent Receivable Schedule Repository
 */
final readonly class EloquentReceivableScheduleRepository implements ReceivableScheduleRepositoryInterface
{
    public function findById(string $id): ?ReceivableScheduleInterface
    {
        return ReceivableSchedule::find($id);
    }

    public function getById(string $id): ReceivableScheduleInterface
    {
        $schedule = $this->findById($id);

        if ($schedule === null) {
            throw ReceivableScheduleNotFoundException::withId($id);
        }

        return $schedule;
    }

    /**
     * @return ReceivableScheduleInterface[]
     */
    public function getByInvoice(string $invoiceId): array
    {
        return ReceivableSchedule::where('customer_invoice_id', $invoiceId)
            ->orderBy('due_date')
            ->get()
            ->all();
    }

    /**
     * @return ReceivableScheduleInterface[]
     */
    public function getUnpaidSchedules(string $tenantId): array
    {
        return ReceivableSchedule::where('tenant_id', $tenantId)
            ->whereNull('paid_date')
            ->orderBy('due_date')
            ->get()
            ->all();
    }

    /**
     * @return ReceivableScheduleInterface[]
     */
    public function getOverdueSchedules(string $tenantId): array
    {
        return ReceivableSchedule::where('tenant_id', $tenantId)
            ->whereNull('paid_date')
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->get()
            ->all();
    }

    public function save(ReceivableScheduleInterface $schedule): void
    {
        if (!$schedule instanceof ReceivableSchedule) {
            throw new \InvalidArgumentException('Schedule must be an Eloquent model');
        }

        $schedule->save();
    }

    public function delete(string $id): void
    {
        $schedule = $this->getById($id);

        if (!$schedule instanceof ReceivableSchedule) {
            throw new \InvalidArgumentException('Schedule must be an Eloquent model');
        }

        $schedule->delete();
    }
}
