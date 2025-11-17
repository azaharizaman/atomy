<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payroll;

use App\Http\Requests\Payroll\ApprovePayslipRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Payroll\Services\PayslipManager;

/**
 * Payslip API Controller
 * 
 * Manages payslips: viewing, approving, marking as paid.
 */
class PayslipController
{
    public function __construct(
        private readonly PayslipManager $payslipManager
    ) {}
    
    /**
     * List payslips with filters.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'employee_id' => $request->input('employee_id'),
            'period_start' => $request->input('period_start'),
            'period_end' => $request->input('period_end'),
            'status' => $request->input('status'),
            'is_approved' => $request->input('is_approved'),
            'is_paid' => $request->input('is_paid'),
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
     * Get a specific payslip with full breakdown.
     * 
     * @param string $payslipId
     * @return JsonResponse
     */
    public function show(string $payslipId): JsonResponse
    {
        $payslip = $this->payslipManager->getPayslip($payslipId);
        
        // Payslip structure includes:
        // - employee details
        // - period details (start, end, pay date)
        // - earnings breakdown (all earning components)
        // - deductions breakdown (all deduction components)
        // - statutory deductions (EPF, SOCSO, EIS, PCB)
        // - gross pay, total deductions, net pay
        // - employer contributions
        // - status (draft, approved, paid)
        
        return response()->json([
            'data' => $payslip,
        ]);
    }
    
    /**
     * Approve a payslip.
     * 
     * @param string $payslipId
     * @param ApprovePayslipRequest $request
     * @return JsonResponse
     */
    public function approve(string $payslipId, ApprovePayslipRequest $request): JsonResponse
    {
        $this->payslipManager->approvePayslip(
            payslipId: $payslipId,
            approverId: auth()->id(),
            comments: $request->input('comments')
        );
        
        return response()->json([
            'message' => 'Payslip approved successfully',
        ]);
    }
    
    /**
     * Mark payslip as paid.
     * 
     * @param string $payslipId
     * @param Request $request
     * @return JsonResponse
     */
    public function markPaid(string $payslipId, Request $request): JsonResponse
    {
        $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|max:100',
            'payment_reference' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:500',
        ]);
        
        $this->payslipManager->markAsPaid(
            payslipId: $payslipId,
            paymentDate: new \DateTime($request->input('payment_date')),
            paymentMethod: $request->input('payment_method'),
            paymentReference: $request->input('payment_reference'),
            remarks: $request->input('remarks')
        );
        
        return response()->json([
            'message' => 'Payslip marked as paid successfully',
        ]);
    }
    
    /**
     * Get all payslips for a specific employee.
     * 
     * @param string $employeeId
     * @param Request $request
     * @return JsonResponse
     */
    public function byEmployee(string $employeeId, Request $request): JsonResponse
    {
        $year = $request->input('year');
        $perPage = min((int) $request->input('per_page', 15), 100);
        $page = (int) $request->input('page', 1);
        
        // This would fetch payslips filtered by employee_id
        return response()->json([
            'data' => [],
            'meta' => [
                'employee_id' => $employeeId,
                'year' => $year,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => 0,
            ],
        ]);
    }
}
