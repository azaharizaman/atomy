<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Http\Requests\Hrm\CreatePerformanceReviewRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Hrm\Services\PerformanceReviewManager;

/**
 * Performance Review API Controller
 * 
 * Manages employee performance reviews.
 */
class PerformanceReviewController
{
    public function __construct(
        private readonly PerformanceReviewManager $reviewManager
    ) {}
    
    /**
     * List performance reviews with filters.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'employee_id' => $request->input('employee_id'),
            'reviewer_id' => $request->input('reviewer_id'),
            'review_type' => $request->input('review_type'),
            'status' => $request->input('status'),
            'period_year' => $request->input('period_year'),
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
     * Create a new performance review.
     * 
     * @param CreatePerformanceReviewRequest $request
     * @return JsonResponse
     */
    public function store(CreatePerformanceReviewRequest $request): JsonResponse
    {
        $reviewId = $this->reviewManager->initiateReview(
            employeeId: $request->input('employee_id'),
            reviewerId: $request->input('reviewer_id'),
            reviewType: $request->input('review_type'),
            reviewPeriodStart: new \DateTime($request->input('review_period_start')),
            reviewPeriodEnd: new \DateTime($request->input('review_period_end')),
            metadata: [
                'goals' => $request->input('goals'),
                'kpis' => $request->input('kpis'),
                'notes' => $request->input('notes'),
            ]
        );
        
        return response()->json([
            'message' => 'Performance review created successfully',
            'data' => [
                'review_id' => $reviewId,
            ],
        ], 201);
    }
    
    /**
     * Get a specific performance review.
     * 
     * @param string $reviewId
     * @return JsonResponse
     */
    public function show(string $reviewId): JsonResponse
    {
        $review = $this->reviewManager->getReview($reviewId);
        
        return response()->json([
            'data' => $review,
        ]);
    }
    
    /**
     * Update a performance review.
     * 
     * @param Request $request
     * @param string $reviewId
     * @return JsonResponse
     */
    public function update(Request $request, string $reviewId): JsonResponse
    {
        $request->validate([
            'overall_rating' => 'nullable|numeric|min:1|max:5',
            'strengths' => 'nullable|string',
            'areas_for_improvement' => 'nullable|string',
            'achievements' => 'nullable|array',
            'goals_next_period' => 'nullable|array',
            'comments' => 'nullable|string',
        ]);
        
        $this->reviewManager->updateReview(
            $reviewId,
            $request->only([
                'overall_rating',
                'strengths',
                'areas_for_improvement',
                'achievements',
                'goals_next_period',
                'comments',
            ])
        );
        
        return response()->json([
            'message' => 'Performance review updated successfully',
        ]);
    }
    
    /**
     * Submit review for approval.
     * 
     * @param string $reviewId
     * @param Request $request
     * @return JsonResponse
     */
    public function submit(string $reviewId, Request $request): JsonResponse
    {
        $this->reviewManager->submitReview($reviewId);
        
        return response()->json([
            'message' => 'Performance review submitted successfully',
        ]);
    }
    
    /**
     * Complete review (final approval).
     * 
     * @param string $reviewId
     * @param Request $request
     * @return JsonResponse
     */
    public function complete(string $reviewId, Request $request): JsonResponse
    {
        $request->validate([
            'approver_id' => 'required|string',
            'final_comments' => 'nullable|string|max:1000',
        ]);
        
        $this->reviewManager->completeReview(
            $reviewId,
            $request->input('approver_id'),
            $request->input('final_comments')
        );
        
        return response()->json([
            'message' => 'Performance review completed successfully',
        ]);
    }
}
