<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payroll;

use App\Http\Requests\Payroll\ProcessPeriodRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Payroll\Services\PayrollEngine;

/**
 * Payroll Processing API Controller
 * 
 * Handles payroll period processing and calculations.
 */
class PayrollController
{
    public function __construct(
        private readonly PayrollEngine $payrollEngine
    ) {}
    
    /**
     * Process payroll for a period.
     * 
     * Calculates payroll for all or specific employees for a given period.
     * 
     * @param ProcessPeriodRequest $request
     * @return JsonResponse
     */
    public function processPeriod(ProcessPeriodRequest $request): JsonResponse
    {
        $periodStart = new \DateTime($request->input('period_start'));
        $periodEnd = new \DateTime($request->input('period_end'));
        $payDate = new \DateTime($request->input('pay_date'));
        
        $filters = [
            'employee_ids' => $request->input('employee_ids'),
            'department_id' => $request->input('department_id'),
            'office_id' => $request->input('office_id'),
        ];
        
        // Process payroll for the period
        $result = $this->payrollEngine->processPeriod(
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            payDate: $payDate,
            filters: $filters,
            metadata: [
                'description' => $request->input('description'),
                'processed_by' => auth()->id(),
            ]
        );
        
        return response()->json([
            'message' => 'Payroll period processed successfully',
            'data' => [
                'period_id' => $result['period_id'] ?? null,
                'employees_processed' => $result['employees_processed'] ?? 0,
                'total_gross_pay' => $result['total_gross_pay'] ?? 0.00,
                'total_deductions' => $result['total_deductions'] ?? 0.00,
                'total_net_pay' => $result['total_net_pay'] ?? 0.00,
                'total_employer_cost' => $result['total_employer_cost'] ?? 0.00,
            ],
        ], 201);
    }
    
    /**
     * Process payroll for a single employee.
     * 
     * Useful for off-cycle payments or corrections.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function processEmployee(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|string',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'pay_date' => 'required|date|after_or_equal:period_end',
            'description' => 'nullable|string|max:255',
            'earnings' => 'nullable|array',
            'earnings.*.component_id' => 'required|string',
            'earnings.*.amount' => 'required|numeric|min:0',
            'deductions' => 'nullable|array',
            'deductions.*.component_id' => 'required|string',
            'deductions.*.amount' => 'required|numeric|min:0',
        ]);
        
        $periodStart = new \DateTime($request->input('period_start'));
        $periodEnd = new \DateTime($request->input('period_end'));
        $payDate = new \DateTime($request->input('pay_date'));
        
        $payslipId = $this->payrollEngine->processEmployee(
            employeeId: $request->input('employee_id'),
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            payDate: $payDate,
            earningsOverride: $request->input('earnings'),
            deductionsOverride: $request->input('deductions'),
            metadata: [
                'description' => $request->input('description'),
                'processed_by' => auth()->id(),
                'is_off_cycle' => true,
            ]
        );
        
        return response()->json([
            'message' => 'Employee payroll processed successfully',
            'data' => [
                'payslip_id' => $payslipId,
            ],
        ], 201);
    }
}
