<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Period\Contracts\PeriodManagerInterface;
use Nexus\Period\Enums\PeriodType;
use Nexus\Period\Exceptions\PeriodException;

/**
 * Period API Controller
 * 
 * Handles HTTP requests for period management operations.
 */
final class PeriodController extends Controller
{
    public function __construct(
        private readonly PeriodManagerInterface $periodManager
    ) {}

    /**
     * List all periods for a type
     * 
     * GET /api/periods?type=accounting&fiscal_year=2024
     */
    public function index(Request $request): JsonResponse
    {
        $type = PeriodType::from($request->input('type', 'accounting'));
        $fiscalYear = $request->input('fiscal_year');
        
        $periods = $this->periodManager->listPeriods($type, $fiscalYear);
        
        return response()->json([
            'data' => array_map(fn($period) => [
                'id' => $period->getId(),
                'type' => $period->getType()->value,
                'status' => $period->getStatus()->value,
                'start_date' => $period->getStartDate()->format('Y-m-d'),
                'end_date' => $period->getEndDate()->format('Y-m-d'),
                'fiscal_year' => $period->getFiscalYear(),
                'name' => $period->getName(),
                'description' => $period->getDescription(),
            ], $periods)
        ]);
    }

    /**
     * Get a specific period by ID
     * 
     * GET /api/periods/{id}
     */
    public function show(string $id): JsonResponse
    {
        try {
            $period = $this->periodManager->findById($id);
            
            return response()->json([
                'data' => [
                    'id' => $period->getId(),
                    'type' => $period->getType()->value,
                    'status' => $period->getStatus()->value,
                    'start_date' => $period->getStartDate()->format('Y-m-d'),
                    'end_date' => $period->getEndDate()->format('Y-m-d'),
                    'fiscal_year' => $period->getFiscalYear(),
                    'name' => $period->getName(),
                    'description' => $period->getDescription(),
                    'is_posting_allowed' => $period->isPostingAllowed(),
                    'created_at' => $period->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $period->getUpdatedAt()->format('Y-m-d H:i:s'),
                ]
            ]);
        } catch (PeriodException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get the currently open period for a type
     * 
     * GET /api/periods/open?type=accounting
     */
    public function openPeriod(Request $request): JsonResponse
    {
        $type = PeriodType::from($request->input('type', 'accounting'));
        $period = $this->periodManager->getOpenPeriod($type);
        
        if ($period === null) {
            return response()->json([
                'data' => null,
                'message' => 'No open period found'
            ], 404);
        }
        
        return response()->json([
            'data' => [
                'id' => $period->getId(),
                'type' => $period->getType()->value,
                'status' => $period->getStatus()->value,
                'start_date' => $period->getStartDate()->format('Y-m-d'),
                'end_date' => $period->getEndDate()->format('Y-m-d'),
                'fiscal_year' => $period->getFiscalYear(),
                'name' => $period->getName(),
                'description' => $period->getDescription(),
            ]
        ]);
    }

    /**
     * Check if posting is allowed for a date and type
     * 
     * POST /api/periods/check-posting
     * Body: { "date": "2024-11-15", "type": "accounting" }
     */
    public function checkPosting(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|string',
        ]);
        
        try {
            $date = new DateTimeImmutable($validated['date']);
            $type = PeriodType::from($validated['type']);
            
            $isAllowed = $this->periodManager->isPostingAllowed($date, $type);
            
            return response()->json([
                'data' => [
                    'is_allowed' => $isAllowed,
                    'date' => $date->format('Y-m-d'),
                    'type' => $type->value,
                ]
            ]);
        } catch (PeriodException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'is_allowed' => false,
            ], 422);
        }
    }

    /**
     * Close a period
     * 
     * POST /api/periods/{id}/close
     * Body: { "reason": "Monthly close completed" }
     */
    public function close(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);
        
        try {
            $userId = auth()->id() ?? 'system';
            $this->periodManager->closePeriod($id, $validated['reason'], $userId);
            
            return response()->json([
                'message' => 'Period closed successfully'
            ]);
        } catch (PeriodException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Reopen a closed period
     * 
     * POST /api/periods/{id}/reopen
     * Body: { "reason": "Correction needed" }
     */
    public function reopen(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);
        
        try {
            $userId = auth()->id() ?? 'system';
            $this->periodManager->reopenPeriod($id, $validated['reason'], $userId);
            
            return response()->json([
                'message' => 'Period reopened successfully'
            ]);
        } catch (PeriodException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }
    }
}
