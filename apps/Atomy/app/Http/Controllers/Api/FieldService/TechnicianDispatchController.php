<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\FieldService;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\FieldService\Services\TechnicianDispatcher;
use Nexus\FieldService\ValueObjects\SkillSet;
use Nexus\FieldService\ValueObjects\GpsLocation;

final class TechnicianDispatchController extends Controller
{
    public function __construct(
        private readonly TechnicianDispatcher $dispatcher
    ) {}

    /**
     * Find best technician for a work order
     */
    public function findBest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'work_order_id' => 'required|string',
            'required_skills' => 'nullable|array',
            'service_location_lat' => 'nullable|numeric',
            'service_location_lng' => 'nullable|numeric',
            'scheduled_start' => 'nullable|date',
        ]);

        $requiredSkills = isset($validated['required_skills'])
            ? SkillSet::fromArray($validated['required_skills'])
            : null;

        $serviceLocation = (isset($validated['service_location_lat']) && isset($validated['service_location_lng']))
            ? GpsLocation::fromCoordinates(
                (float) $validated['service_location_lat'],
                (float) $validated['service_location_lng']
            )
            : null;

        $scheduledStart = isset($validated['scheduled_start'])
            ? new \DateTimeImmutable($validated['scheduled_start'])
            : null;

        $result = $this->dispatcher->findBestTechnician(
            $validated['work_order_id'],
            $requiredSkills,
            $serviceLocation,
            $scheduledStart
        );

        return response()->json([
            'data' => [
                'technician_id' => $result['technician_id'],
                'score' => $result['score'],
                'breakdown' => $result['breakdown'] ?? null,
            ],
        ]);
    }

    /**
     * Auto-assign work order to best available technician
     */
    public function autoAssign(string $workOrderId): JsonResponse
    {
        $this->dispatcher->autoAssign($workOrderId);

        return response()->json([
            'message' => 'Work order auto-assigned successfully',
        ]);
    }

    /**
     * Optimize route for technician
     */
    public function optimizeRoute(string $technicianId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $date = new \DateTimeImmutable($validated['date']);
        
        $optimizedRoute = $this->dispatcher->optimizeRoute($technicianId, $date);

        return response()->json([
            'data' => [
                'technician_id' => $technicianId,
                'date' => $date->format('Y-m-d'),
                'work_orders' => array_map(fn($wo) => [
                    'id' => $wo->getId(),
                    'number' => $wo->getNumber()->toString(),
                    'scheduled_start' => $wo->getScheduledStart()?->format('Y-m-d H:i:s'),
                ], $optimizedRoute),
            ],
        ]);
    }
}
