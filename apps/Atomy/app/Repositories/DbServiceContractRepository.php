<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ServiceContract;
use Nexus\FieldService\Contracts\ServiceContractInterface;
use Nexus\FieldService\Contracts\ServiceContractRepositoryInterface;
use Nexus\FieldService\Enums\ContractStatus;
use Nexus\FieldService\Exceptions\ServiceContractNotFoundException;

final readonly class DbServiceContractRepository implements ServiceContractRepositoryInterface
{
    public function __construct(
        private string $tenantId
    ) {}

    public function findById(string $id): ServiceContractInterface
    {
        $contract = ServiceContract::forTenant($this->tenantId)->find($id);

        if ($contract === null) {
            throw ServiceContractNotFoundException::forId($id);
        }

        return $contract;
    }

    public function findByContractNumber(string $contractNumber): ?ServiceContractInterface
    {
        return ServiceContract::forTenant($this->tenantId)
            ->where('contract_number', $contractNumber)
            ->first();
    }

    public function save(ServiceContractInterface $contract): void
    {
        if ($contract instanceof ServiceContract) {
            $contract->save();
            return;
        }

        throw new \InvalidArgumentException('ServiceContract must be an Eloquent model');
    }

    public function delete(string $id): void
    {
        $contract = ServiceContract::forTenant($this->tenantId)->find($id);

        if ($contract === null) {
            throw ServiceContractNotFoundException::forId($id);
        }

        $contract->delete();
    }

    public function getActiveContracts(): array
    {
        return ServiceContract::forTenant($this->tenantId)
            ->active()
            ->orderBy('end_date', 'asc')
            ->get()
            ->all();
    }

    public function getByCustomer(string $customerPartyId): array
    {
        return ServiceContract::forTenant($this->tenantId)
            ->where('customer_party_id', $customerPartyId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function getByAsset(string $assetId): array
    {
        return ServiceContract::forTenant($this->tenantId)
            ->where('asset_id', $assetId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function getExpiringSoon(int $days = 30): array
    {
        return ServiceContract::forTenant($this->tenantId)
            ->expiringSoon($days)
            ->orderBy('end_date', 'asc')
            ->get()
            ->all();
    }

    public function getDueForMaintenance(): array
    {
        return ServiceContract::forTenant($this->tenantId)
            ->dueForMaintenance()
            ->get()
            ->all();
    }

    public function countByStatus(ContractStatus $status): int
    {
        return ServiceContract::forTenant($this->tenantId)
            ->where('status', $status)
            ->count();
    }

    public function generateNextContractNumber(string $year): string
    {
        $prefix = "SC-{$year}-";
        
        $lastContract = ServiceContract::forTenant($this->tenantId)
            ->where('contract_number', 'LIKE', "{$prefix}%")
            ->orderBy('contract_number', 'desc')
            ->first();

        if ($lastContract === null) {
            return "{$prefix}00001";
        }

        $lastNumber = (int) substr($lastContract->contract_number, -5);
        $nextNumber = str_pad((string)($lastNumber + 1), 5, '0', STR_PAD_LEFT);

        return $prefix . $nextNumber;
    }
}
