<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\FieldService;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Models\CustomerSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\FieldService\Services\PartsConsumptionManager;
use Nexus\FieldService\Contracts\MobileSyncManagerInterface;
use Nexus\FieldService\Contracts\WorkOrderRepositoryInterface;
use Nexus\FieldService\ValueObjects\CustomerSignature as CustomerSignatureVO;
use Nexus\Tenant\Contracts\TenantContextInterface;

/**
 * Mobile Work Order Controller
 *
 * Optimized endpoints for mobile field technician app
 */
final class MobileWorkOrderController extends Controller
{
    public function __construct(
        private readonly WorkOrderRepositoryInterface $repository,
        private readonly PartsConsumptionManager $partsManager,
        private readonly MobileSyncManagerInterface $syncManager,
        private readonly TenantContextInterface $tenantContext
    ) {}

    /**
     * Get work orders assigned to technician
     */
    public function myWorkOrders(Request $request): JsonResponse
    {
        $technicianId = $request->input('technician_id'); // Should come from auth context
        $workOrders = $this->repository->getByTechnician($technicianId);

        return response()->json([
            'data' => array_map(fn($wo) => $this->formatMobileWorkOrder($wo), $workOrders),
        ]);
    }

    /**
     * Capture customer signature
     */
    public function captureSignature(string $workOrderId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'signature_data' => 'required|string',
            'customer_name' => 'nullable|string',
            'gps_latitude' => 'nullable|numeric',
            'gps_longitude' => 'nullable|numeric',
        ]);

        $signature = CustomerSignatureVO::create(
            $validated['signature_data'],
            $validated['customer_name'] ?? null
        );

        $gpsLocation = (isset($validated['gps_latitude']) && isset($validated['gps_longitude']))
            ? ['latitude' => $validated['gps_latitude'], 'longitude' => $validated['gps_longitude']]
            : null;

        CustomerSignature::create([
            'work_order_id' => $workOrderId,
            'signature_data' => $signature->getSignatureData(),
            'signature_hash' => $signature->getHash(),
            'customer_name' => $signature->getCustomerName(),
            'gps_location' => $gpsLocation,
            'captured_at' => now(),
        ]);

        return response()->json([
            'message' => 'Signature captured successfully',
        ], 201);
    }

    /**
     * Record parts consumption
     */
    public function consumeParts(string $workOrderId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_variant_id' => 'required|string',
            'quantity' => 'required|numeric|min:0',
            'uom' => 'required|string',
        ]);

        $this->partsManager->consume(
            $workOrderId,
            $validated['product_variant_id'],
            (float) $validated['quantity'],
            $validated['uom']
        );

        return response()->json([
            'message' => 'Parts consumption recorded',
        ], 201);
    }

    /**
     * Sync offline changes
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'changes' => 'required|array',
        ]);

        $conflicts = $this->syncManager->sync($validated['changes']);

        return response()->json([
            'message' => 'Sync completed',
            'conflicts' => $conflicts,
        ]);
    }

    /**
     * Resolve sync conflict
     */
    public function resolveConflict(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_id' => 'required|string',
            'resolution_strategy' => 'required|string|in:local,remote',
            'local_version' => 'required|array',
            'remote_version' => 'required|array',
        ]);

        $resolved = $this->syncManager->resolveConflict(
            $validated['entity_id'],
            $validated['resolution_strategy'],
            $validated['local_version'],
            $validated['remote_version']
        );

        return response()->json([
            'data' => $resolved,
        ]);
    }

    private function formatMobileWorkOrder($workOrder): array
    {
        return [
            'id' => $workOrder->getId(),
            'number' => $workOrder->getNumber()->toString(),
            'status' => $workOrder->getStatus()->value,
            'priority' => $workOrder->getPriority()->value,
            'description' => $workOrder->getDescription(),
            'scheduled_start' => $workOrder->getScheduledStart()?->format('Y-m-d H:i:s'),
            'scheduled_end' => $workOrder->getScheduledEnd()?->format('Y-m-d H:i:s'),
            'sla_deadline' => $workOrder->getSlaDeadline()?->format('Y-m-d H:i:s'),
        ];
    }
}
