<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payroll;

use App\Http\Requests\Payroll\CreateComponentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Payroll\Services\ComponentManager;

/**
 * Payroll Component API Controller
 * 
 * Manages payroll components (earnings and deductions).
 */
class ComponentController
{
    public function __construct(
        private readonly ComponentManager $componentManager
    ) {}
    
    /**
     * List payroll components with filters.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'type' => $request->input('type'), // 'earning' or 'deduction'
            'calculation_method' => $request->input('calculation_method'),
            'is_active' => $request->input('is_active'),
            'is_statutory' => $request->input('is_statutory'),
            'search' => $request->input('search'),
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
     * Create a new payroll component.
     * 
     * @param CreateComponentRequest $request
     * @return JsonResponse
     */
    public function store(CreateComponentRequest $request): JsonResponse
    {
        $componentId = $this->componentManager->createComponent(
            code: $request->input('code'),
            name: $request->input('name'),
            type: $request->input('type'),
            calculationMethod: $request->input('calculation_method'),
            metadata: [
                'description' => $request->input('description'),
                'fixed_amount' => $request->input('fixed_amount'),
                'percentage_value' => $request->input('percentage_value'),
                'percentage_base' => $request->input('percentage_base'),
                'formula' => $request->input('formula'),
                'is_taxable' => $request->input('is_taxable', true),
                'is_statutory' => $request->input('is_statutory', false),
                'gl_account_code' => $request->input('gl_account_code'),
                'sort_order' => $request->input('sort_order', 0),
            ]
        );
        
        return response()->json([
            'message' => 'Payroll component created successfully',
            'data' => [
                'component_id' => $componentId,
            ],
        ], 201);
    }
    
    /**
     * Get a specific payroll component.
     * 
     * @param string $componentId
     * @return JsonResponse
     */
    public function show(string $componentId): JsonResponse
    {
        $component = $this->componentManager->getComponent($componentId);
        
        return response()->json([
            'data' => $component,
        ]);
    }
    
    /**
     * Update a payroll component.
     * 
     * @param Request $request
     * @param string $componentId
     * @return JsonResponse
     */
    public function update(Request $request, string $componentId): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'calculation_method' => 'sometimes|required|string',
            'fixed_amount' => 'nullable|numeric|min:0',
            'percentage_value' => 'nullable|numeric|min:0|max:100',
            'percentage_base' => 'nullable|string',
            'formula' => 'nullable|string',
            'is_taxable' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);
        
        $this->componentManager->updateComponent(
            $componentId,
            $request->only([
                'name',
                'description',
                'calculation_method',
                'fixed_amount',
                'percentage_value',
                'percentage_base',
                'formula',
                'is_taxable',
                'is_active',
                'gl_account_code',
                'sort_order',
            ])
        );
        
        return response()->json([
            'message' => 'Payroll component updated successfully',
        ]);
    }
    
    /**
     * Delete a payroll component.
     * 
     * @param string $componentId
     * @return JsonResponse
     */
    public function destroy(string $componentId): JsonResponse
    {
        $this->componentManager->deleteComponent($componentId);
        
        return response()->json([
            'message' => 'Payroll component deleted successfully',
        ]);
    }
}
