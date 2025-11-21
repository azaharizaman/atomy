<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\WorkOrder;
use Nexus\FieldService\Contracts\WorkOrderInterface;
use Nexus\FieldService\Contracts\WorkOrderRepositoryInterface;
use Nexus\FieldService\Enums\WorkOrderStatus;
use Nexus\FieldService\Exceptions\WorkOrderNotFoundException;
use Nexus\FieldService\ValueObjects\WorkOrderNumber;

final readonly class DbWorkOrderRepository implements WorkOrderRepositoryInterface
{
    public function __construct(
        private string $tenantId
    ) {}

    public function findById(string $id): WorkOrderInterface
    {
        $workOrder = WorkOrder::forTenant($this->tenantId)->find($id);

        if ($workOrder === null) {
            throw WorkOrderNotFoundException::forId($id);
        }

        return $workOrder;
    }

    public function findByNumber(WorkOrderNumber $number): ?WorkOrderInterface
    {
        return WorkOrder::forTenant($this->tenantId)
            ->where('number', $number->toString())
            ->first();
    }

    public function save(WorkOrderInterface $workOrder): void
    {
        if ($workOrder instanceof WorkOrder) {
            $workOrder->save();
            return;
        }

        throw new \InvalidArgumentException('WorkOrder must be an Eloquent model');
    }

    public function delete(string $id): void
    {
        $workOrder = WorkOrder::forTenant($this->tenantId)->find($id);

        if ($workOrder === null) {
            throw WorkOrderNotFoundException::forId($id);
        }

        $workOrder->delete();
    }

    public function getByStatus(WorkOrderStatus $status): array
    {
        return WorkOrder::forTenant($this->tenantId)
            ->byStatus($status)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function getActiveWorkOrders(): array
    {
        return WorkOrder::forTenant($this->tenantId)
            ->active()
            ->orderBy('priority', 'desc')
            ->orderBy('scheduled_start', 'asc')
            ->get()
            ->all();
    }

    public function getByTechnician(string $technicianId): array
    {
        return WorkOrder::forTenant($this->tenantId)
            ->where('assigned_technician_id', $technicianId)
            ->active()
            ->orderBy('scheduled_start', 'asc')
            ->get()
            ->all();
    }

    public function getApproachingSla(\DateTimeInterface $threshold): array
    {
        return WorkOrder::forTenant($this->tenantId)
            ->approachingSla($threshold)
            ->orderBy('sla_deadline', 'asc')
            ->get()
            ->all();
    }

    public function getByServiceContract(string $contractId): array
    {
        return WorkOrder::forTenant($this->tenantId)
            ->where('service_contract_id', $contractId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function getByCustomer(string $customerPartyId): array
    {
        return WorkOrder::forTenant($this->tenantId)
            ->where('customer_party_id', $customerPartyId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function getScheduledBetween(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return WorkOrder::forTenant($this->tenantId)
            ->whereBetween('scheduled_start', [$start, $end])
            ->orderBy('scheduled_start', 'asc')
            ->get()
            ->all();
    }

    public function countByStatus(WorkOrderStatus $status): int
    {
        return WorkOrder::forTenant($this->tenantId)
            ->byStatus($status)
            ->count();
    }

    public function generateNextNumber(string $year): WorkOrderNumber
    {
        $prefix = "WO-{$year}-";
        
        $lastWorkOrder = WorkOrder::forTenant($this->tenantId)
            ->where('number', 'LIKE', "{$prefix}%")
            ->orderBy('number', 'desc')
            ->first();

        if ($lastWorkOrder === null) {
            return WorkOrderNumber::fromString("{$prefix}00001");
        }

        $lastNumber = (int) substr($lastWorkOrder->number, -5);
        $nextNumber = str_pad((string)($lastNumber + 1), 5, '0', STR_PAD_LEFT);

        return WorkOrderNumber::fromString($prefix . $nextNumber);
    }
}
