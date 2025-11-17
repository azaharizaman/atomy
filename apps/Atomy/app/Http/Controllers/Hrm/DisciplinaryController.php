<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Http\Requests\Hrm\CreateDisciplinaryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Hrm\Services\DisciplinaryManager;

/**
 * Disciplinary Case API Controller
 * 
 * Manages employee disciplinary cases.
 */
class DisciplinaryController
{
    public function __construct(
        private readonly DisciplinaryManager $disciplinaryManager
    ) {}
    
    /**
     * List disciplinary cases with filters.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'employee_id' => $request->input('employee_id'),
            'severity' => $request->input('severity'),
            'status' => $request->input('status'),
            'category' => $request->input('category'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];
        
        $perPage = min((int) $request->input('per_page', 15), 100);
        $page = (int) $request->input('page', 1);
        
        return response()->json([
            'data' => [],
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => 0,
            ],
        ]);
    }
    
    /**
     * Create a new disciplinary case.
     * 
     * @param CreateDisciplinaryRequest $request
     * @return JsonResponse
     */
    public function store(CreateDisciplinaryRequest $request): JsonResponse
    {
        $caseId = $this->disciplinaryManager->openCase(
            employeeId: $request->input('employee_id'),
            reportedBy: $request->input('reported_by'),
            incidentDate: new \DateTime($request->input('incident_date')),
            severity: $request->input('severity'),
            category: $request->input('category'),
            description: $request->input('description'),
            metadata: [
                'location' => $request->input('location'),
                'witnesses' => $request->input('witnesses'),
                'evidence' => $request->input('evidence'),
            ]
        );
        
        return response()->json([
            'message' => 'Disciplinary case created successfully',
            'data' => [
                'case_id' => $caseId,
            ],
        ], 201);
    }
    
    /**
     * Get a specific disciplinary case.
     * 
     * @param string $caseId
     * @return JsonResponse
     */
    public function show(string $caseId): JsonResponse
    {
        $case = $this->disciplinaryManager->getCase($caseId);
        
        return response()->json([
            'data' => $case,
        ]);
    }
    
    /**
     * Update a disciplinary case.
     * 
     * @param Request $request
     * @param string $caseId
     * @return JsonResponse
     */
    public function update(Request $request, string $caseId): JsonResponse
    {
        $request->validate([
            'severity' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'investigation_notes' => 'nullable|string',
        ]);
        
        $this->disciplinaryManager->updateCase(
            $caseId,
            $request->only([
                'severity',
                'category',
                'description',
                'investigation_notes',
            ])
        );
        
        return response()->json([
            'message' => 'Disciplinary case updated successfully',
        ]);
    }
    
    /**
     * Mark case under investigation.
     * 
     * @param string $caseId
     * @param Request $request
     * @return JsonResponse
     */
    public function investigate(string $caseId, Request $request): JsonResponse
    {
        $request->validate([
            'investigator_id' => 'required|string',
            'investigation_notes' => 'nullable|string',
        ]);
        
        $this->disciplinaryManager->investigateCase(
            $caseId,
            $request->input('investigator_id'),
            $request->input('investigation_notes')
        );
        
        return response()->json([
            'message' => 'Case marked under investigation',
        ]);
    }
    
    /**
     * Resolve case with action taken.
     * 
     * @param string $caseId
     * @param Request $request
     * @return JsonResponse
     */
    public function resolve(string $caseId, Request $request): JsonResponse
    {
        $request->validate([
            'resolution' => 'required|string',
            'action_taken' => 'required|string',
            'resolved_by' => 'required|string',
            'resolution_date' => 'required|date',
        ]);
        
        $this->disciplinaryManager->resolveCase(
            $caseId,
            $request->input('resolution'),
            $request->input('action_taken'),
            $request->input('resolved_by'),
            new \DateTime($request->input('resolution_date'))
        );
        
        return response()->json([
            'message' => 'Disciplinary case resolved successfully',
        ]);
    }
    
    /**
     * Close case (no action required).
     * 
     * @param string $caseId
     * @param Request $request
     * @return JsonResponse
     */
    public function close(string $caseId, Request $request): JsonResponse
    {
        $request->validate([
            'closure_reason' => 'required|string|max:500',
            'closed_by' => 'required|string',
        ]);
        
        $this->disciplinaryManager->closeCase(
            $caseId,
            $request->input('closure_reason'),
            $request->input('closed_by')
        );
        
        return response()->json([
            'message' => 'Disciplinary case closed successfully',
        ]);
    }
}
