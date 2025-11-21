<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\FieldService;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\FieldService\Services\WorkOrderManager;
use Nexus\FieldService\Enums\WorkOrderStatus;
use Nexus\FieldService\Enums\WorkOrderPriority;
use Nexus\FieldService\Enums\ServiceType;
use Nexus\FieldService\ValueObjects\LaborHours;
use Nexus\FieldService\Contracts\WorkOrderRepositoryInterface;

final class WorkOrderController extends Controller
{
    public function __construct(
        private readonly WorkOrderManager $workOrderManager,
        private readonly WorkOrderRepositoryInterface $repository
    ) {}

    /**
     * List work orders with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->input('status');
        $technicianId = $request->input('technician_id');
        $customerPartyId = $request->input('customer_party_id');

        if ($status) {
            $workOrders = $this->repository->getByStatus(WorkOrderStatus::from($status));
        } elseif ($technicianId) {
            $workOrders = $this->repository->getByTechnician($technicianId);
        } elseif ($customerPartyId) {
            $workOrders = $this->repository->getByCustomer($customerPartyId);
        } else {
            $workOrders = $this->repository->getActiveWorkOrders();
        }

        return response()->json([
            'data' => array_map(fn($wo) => $this->formatWorkOrder($wo), $workOrders),
        ]);
    }

    /**
     * Get single work order
     */
    public function show(string $id): JsonResponse
    {
        $workOrder = $this->repository->findById($id);

        return response()->json([
            'data' => $this->formatWorkOrder($workOrder),
        ]);
    }

    /**
     * Create new work order
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_party_id' => 'required|string',
            'service_location_id' => 'nullable|string',
            'asset_id' => 'nullable|string',
            'service_contract_id' => 'nullable|string',
            'priority' => 'required|string',
            'service_type' => 'required|string',
            'description' => 'required|string',
            'scheduled_start' => 'nullable|date',
            'scheduled_end' => 'nullable|date',
        ]);

        $workOrder = $this->workOrderManager->create(
            $validated['customer_party_id'],
            $validated['service_location_id'] ?? null,
            $validated['asset_id'] ?? null,
            $validated['service_contract_id'] ?? null,
            WorkOrderPriority::from($validated['priority']),
            ServiceType::from($validated['service_type']),
            $validated['description'],
            isset($validated['scheduled_start']) ? new \DateTimeImmutable($validated['scheduled_start']) : null,
            isset($validated['scheduled_end']) ? new \DateTimeImmutable($validated['scheduled_end']) : null
        );

        return response()->json([
            'data' => $this->formatWorkOrder($workOrder),
        ], 201);
    }

    /**
     * Assign work order to technician
     */
    public function assign(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'technician_id' => 'required|string',
        ]);

        $this->workOrderManager->assign($id, $validated['technician_id']);

        return response()->json([
            'message' => 'Work order assigned successfully',
        ]);
    }

    /**
     * Start work order
     */
    public function start(string $id): JsonResponse
    {
        $this->workOrderManager->start($id);

        return response()->json([
            'message' => 'Work order started',
        ]);
    }

    /**
     * Complete work order
     */
    public function complete(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'technician_notes' => 'nullable|string',
            'labor_hours' => 'required|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
        ]);

        $laborHours = null;
        if (isset($validated['labor_hours'])) {
            $laborHours = LaborHours::create(
                (float) $validated['labor_hours'],
                isset($validated['hourly_rate']) ? (float) $validated['hourly_rate'] : null,
                $validated['currency'] ?? 'MYR'
            );
        }

        $this->workOrderManager->complete(
            $id,
            $validated['technician_notes'] ?? null,
            $laborHours
        );

        return response()->json([
            'message' => 'Work order completed',
        ]);
    }

    /**
     * Verify work order
     */
    public function verify(string $id): JsonResponse
    {
        $this->workOrderManager->verify($id);

        return response()->json([
            'message' => 'Work order verified',
        ]);
    }

    /**
     * Cancel work order
     */
    public function cancel(string $id): JsonResponse
    {
        $this->workOrderManager->cancel($id);

        return response()->json([
            'message' => 'Work order cancelled',
        ]);
    }

    /**
     * Get SLA status
     */
    public function slaStatus(Request $request): JsonResponse
    {
        $threshold = $request->input('threshold', 'now +4 hours');
        $approachingSla = $this->repository->getApproachingSla(new \DateTimeImmutable($threshold));

        return response()->json([
            'data' => array_map(fn($wo) => [
                'id' => $wo->getId(),
                'number' => $wo->getNumber()->toString(),
                'sla_deadline' => $wo->getSlaDeadline()?->format('Y-m-d H:i:s'),
                'status' => $wo->getStatus()->value,
                'priority' => $wo->getPriority()->value,
            ], $approachingSla),
        ]);
    }

    private function formatWorkOrder($workOrder): array
    {
        return [
            'id' => $workOrder->getId(),
            'number' => $workOrder->getNumber()->toString(),
            'customer_party_id' => $workOrder->getCustomerPartyId(),
            'service_location_id' => $workOrder->getServiceLocationId(),
            'asset_id' => $workOrder->getAssetId(),
            'service_contract_id' => $workOrder->getServiceContractId(),
            'assigned_technician_id' => $workOrder->getAssignedTechnicianId(),
            'status' => $workOrder->getStatus()->value,
            'priority' => $workOrder->getPriority()->value,
            'service_type' => $workOrder->getServiceType()->value,
            'description' => $workOrder->getDescription(),
            'scheduled_start' => $workOrder->getScheduledStart()?->format('Y-m-d H:i:s'),
            'scheduled_end' => $workOrder->getScheduledEnd()?->format('Y-m-d H:i:s'),
            'actual_start' => $workOrder->getActualStart()?->format('Y-m-d H:i:s'),
            'actual_end' => $workOrder->getActualEnd()?->format('Y-m-d H:i:s'),
            'sla_deadline' => $workOrder->getSlaDeadline()?->format('Y-m-d H:i:s'),
            'technician_notes' => $workOrder->getTechnicianNotes(),
            'labor_hours' => $workOrder->getLaborHours()?->getHours(),
            'labor_cost' => $workOrder->getLaborHours()?->getTotalCost(),
            'created_at' => $workOrder->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $workOrder->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
