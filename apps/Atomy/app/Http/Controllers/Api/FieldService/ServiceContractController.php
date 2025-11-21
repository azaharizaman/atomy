<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\FieldService;

use App\Http\Controllers\Controller;
use App\Models\ServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\FieldService\Contracts\ServiceContractRepositoryInterface;
use Nexus\FieldService\Enums\ContractStatus;
use Nexus\Tenant\Contracts\TenantContextInterface;

final class ServiceContractController extends Controller
{
    public function __construct(
        private readonly ServiceContractRepositoryInterface $repository,
        private readonly TenantContextInterface $tenantContext
    ) {}

    /**
     * List service contracts
     */
    public function index(Request $request): JsonResponse
    {
        $customerPartyId = $request->input('customer_party_id');
        $assetId = $request->input('asset_id');
        $activeOnly = $request->boolean('active_only', false);

        if ($customerPartyId) {
            $contracts = $this->repository->getByCustomer($customerPartyId);
        } elseif ($assetId) {
            $contracts = $this->repository->getByAsset($assetId);
        } elseif ($activeOnly) {
            $contracts = $this->repository->getActiveContracts();
        } else {
            $contracts = ServiceContract::forTenant($this->tenantContext->getCurrentTenantId())
                ->orderBy('created_at', 'desc')
                ->get()
                ->all();
        }

        return response()->json([
            'data' => array_map(fn($c) => $this->formatContract($c), $contracts),
        ]);
    }

    /**
     * Get single contract
     */
    public function show(string $id): JsonResponse
    {
        $contract = $this->repository->findById($id);

        return response()->json([
            'data' => $this->formatContract($contract),
        ]);
    }

    /**
     * Create service contract
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_party_id' => 'required|string',
            'asset_id' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'response_time' => 'nullable|string',
            'maintenance_interval_days' => 'nullable|integer|min:1',
            'covered_services' => 'required|array',
        ]);

        $contractNumber = $this->repository->generateNextContractNumber(date('Y'));

        $contract = ServiceContract::create([
            'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            'contract_number' => $contractNumber,
            'customer_party_id' => $validated['customer_party_id'],
            'asset_id' => $validated['asset_id'] ?? null,
            'status' => ContractStatus::ACTIVE,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'response_time' => $validated['response_time'] ?? null,
            'maintenance_interval_days' => $validated['maintenance_interval_days'] ?? null,
            'covered_services' => $validated['covered_services'],
        ]);

        return response()->json([
            'data' => $this->formatContract($contract),
        ], 201);
    }

    /**
     * Update service contract
     */
    public function update(string $id, Request $request): JsonResponse
    {
        $contract = ServiceContract::forTenant($this->tenantContext->getCurrentTenantId())->findOrFail($id);

        $validated = $request->validate([
            'status' => 'nullable|string',
            'end_date' => 'nullable|date',
            'response_time' => 'nullable|string',
            'maintenance_interval_days' => 'nullable|integer|min:1',
            'covered_services' => 'nullable|array',
        ]);

        $contract->update(array_filter($validated));

        return response()->json([
            'data' => $this->formatContract($contract),
        ]);
    }

    /**
     * Get expiring contracts
     */
    public function expiring(Request $request): JsonResponse
    {
        $days = $request->integer('days', 30);
        $contracts = $this->repository->getExpiringSoon($days);

        return response()->json([
            'data' => array_map(fn($c) => $this->formatContract($c), $contracts),
        ]);
    }

    /**
     * Get contracts due for maintenance
     */
    public function dueForMaintenance(): JsonResponse
    {
        $contracts = $this->repository->getDueForMaintenance();

        return response()->json([
            'data' => array_map(fn($c) => $this->formatContract($c), $contracts),
        ]);
    }

    private function formatContract($contract): array
    {
        return [
            'id' => $contract->getId(),
            'contract_number' => $contract->getContractNumber(),
            'customer_party_id' => $contract->getCustomerPartyId(),
            'asset_id' => $contract->getAssetId(),
            'status' => $contract->getStatus()->value,
            'start_date' => $contract->getStartDate()->format('Y-m-d'),
            'end_date' => $contract->getEndDate()->format('Y-m-d'),
            'response_time' => $contract->getResponseTime(),
            'maintenance_interval_days' => $contract->getMaintenanceIntervalDays(),
            'covered_services' => $contract->getCoveredServices(),
            'is_active' => $contract->isActive(),
            'created_at' => $contract->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $contract->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
