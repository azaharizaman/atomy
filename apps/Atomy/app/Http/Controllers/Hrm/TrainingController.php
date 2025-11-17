<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Http\Requests\Hrm\CreateTrainingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Hrm\Services\TrainingManager;

/**
 * Training Program API Controller
 * 
 * Manages training programs and enrollments.
 */
class TrainingController
{
    public function __construct(
        private readonly TrainingManager $trainingManager
    ) {}
    
    /**
     * List training programs with filters.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'category' => $request->input('category'),
            'trainer_id' => $request->input('trainer_id'),
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
     * Create a new training program.
     * 
     * @param CreateTrainingRequest $request
     * @return JsonResponse
     */
    public function store(CreateTrainingRequest $request): JsonResponse
    {
        $trainingId = $this->trainingManager->createTrainingProgram(
            title: $request->input('title'),
            description: $request->input('description'),
            category: $request->input('category'),
            trainerId: $request->input('trainer_id'),
            metadata: [
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'location' => $request->input('location'),
                'max_participants' => $request->input('max_participants'),
                'duration_hours' => $request->input('duration_hours'),
                'cost' => $request->input('cost'),
                'materials' => $request->input('materials'),
            ]
        );
        
        return response()->json([
            'message' => 'Training program created successfully',
            'data' => [
                'training_id' => $trainingId,
            ],
        ], 201);
    }
    
    /**
     * Get a specific training program.
     * 
     * @param string $trainingId
     * @return JsonResponse
     */
    public function show(string $trainingId): JsonResponse
    {
        $training = $this->trainingManager->getTrainingProgram($trainingId);
        
        return response()->json([
            'data' => $training,
        ]);
    }
    
    /**
     * Update a training program.
     * 
     * @param Request $request
     * @param string $trainingId
     * @return JsonResponse
     */
    public function update(Request $request, string $trainingId): JsonResponse
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'category' => 'sometimes|required|string|max:100',
            'trainer_id' => 'sometimes|required|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location' => 'nullable|string|max:255',
            'max_participants' => 'nullable|integer|min:1',
            'duration_hours' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
        ]);
        
        $this->trainingManager->updateTrainingProgram(
            $trainingId,
            $request->only([
                'title',
                'description',
                'category',
                'trainer_id',
                'start_date',
                'end_date',
                'location',
                'max_participants',
                'duration_hours',
                'cost',
                'materials',
            ])
        );
        
        return response()->json([
            'message' => 'Training program updated successfully',
        ]);
    }
    
    /**
     * Enroll employee in training program.
     * 
     * @param string $trainingId
     * @param Request $request
     * @return JsonResponse
     */
    public function enroll(string $trainingId, Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|string',
            'enrollment_notes' => 'nullable|string|max:500',
        ]);
        
        $enrollmentId = $this->trainingManager->enrollEmployee(
            $trainingId,
            $request->input('employee_id'),
            $request->input('enrollment_notes')
        );
        
        return response()->json([
            'message' => 'Employee enrolled successfully',
            'data' => [
                'enrollment_id' => $enrollmentId,
            ],
        ], 201);
    }
    
    /**
     * Complete training enrollment.
     * 
     * @param string $trainingId
     * @param Request $request
     * @return JsonResponse
     */
    public function completeEnrollment(string $trainingId, Request $request): JsonResponse
    {
        $request->validate([
            'enrollment_id' => 'required|string',
            'completion_date' => 'required|date',
            'assessment_score' => 'nullable|numeric|min:0|max:100',
            'feedback' => 'nullable|string',
            'certificate_issued' => 'nullable|boolean',
        ]);
        
        $this->trainingManager->completeEnrollment(
            $request->input('enrollment_id'),
            new \DateTime($request->input('completion_date')),
            $request->input('assessment_score'),
            $request->input('feedback'),
            $request->input('certificate_issued', false)
        );
        
        return response()->json([
            'message' => 'Training enrollment completed successfully',
        ]);
    }
}
