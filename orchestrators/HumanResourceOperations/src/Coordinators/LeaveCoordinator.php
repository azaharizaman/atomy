<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Coordinators;

use Nexus\HumanResourceOperations\DTOs\LeaveApplicationRequest;
use Nexus\HumanResourceOperations\DTOs\LeaveApplicationResult;
use Nexus\HumanResourceOperations\DTOs\LeaveContext;
use Nexus\HumanResourceOperations\DataProviders\LeaveDataProvider;
use Nexus\HumanResourceOperations\Services\LeaveRuleRegistry;
use Nexus\HumanResourceOperations\Services\WorkingDaysCalculationService;
use Nexus\HumanResourceOperations\Services\LeaveBalanceService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for leave application operations.
 * 
 * Following Advanced Orchestrator Pattern:
 * - Traffic cop: directs flow, doesn't do work
 * - Calls DataProvider for context
 * - Calls RuleRegistry for validation
 * - Delegates calculations to Services
 * - Delegates to atomic packages for execution
 */
final readonly class LeaveCoordinator
{
    public function __construct(
        private LeaveDataProvider $dataProvider,
        private LeaveRuleRegistry $ruleRegistry,
        private WorkingDaysCalculationService $workingDaysCalc,
        private LeaveBalanceService $leaveBalanceService,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Process leave application.
     * 
     * Flow:
     * 1. Calculate days if not provided (via Service)
     * 2. Get leave context from DataProvider
     * 3. Validate using Rules
     * 4. If valid, create leave request (via Service)
     * 5. Return result
     */
    public function applyLeave(LeaveApplicationRequest $request): LeaveApplicationResult
    {
        $this->logger->info('Processing leave application', [
            'employee_id' => $request->employeeId,
            'leave_type' => $request->leaveTypeId,
            'start_date' => $request->startDate,
            'end_date' => $request->endDate,
        ]);

        // Step 1: Calculate days if not provided (delegated to Service)
        $daysRequested = $request->daysRequested ?? $this->workingDaysCalc->calculate(
            $request->startDate,
            $request->endDate
        );

        // Step 2: Get leave context (DataProvider aggregates cross-package data)
        $context = $this->dataProvider->getLeaveContext(
            employeeId: $request->employeeId,
            leaveTypeId: $request->leaveTypeId,
            startDate: $request->startDate,
            endDate: $request->endDate,
            daysRequested: $daysRequested,
            applicantUserId: $request->applicantUserId,
            applicantName: $request->applicantName,
        );

        // Step 3: Validate using Rules
        $issues = [];
        foreach ($this->ruleRegistry->all() as $rule) {
            $result = $rule->check($context);
            if (!$result->passed) {
                $issues[] = [
                    'rule' => $result->ruleName,
                    'severity' => $result->severity,
                    'message' => $result->message,
                ];
            }
        }

        if (!empty($issues)) {
            $this->logger->warning('Leave application validation failed', [
                'employee_id' => $request->employeeId,
                'issues' => $issues,
            ]);

            return new LeaveApplicationResult(
                success: false,
                message: 'Leave application does not meet policy requirements',
                issues: $issues,
            );
        }

        // Step 4: Create leave request (delegated to Service)
        try {
            $result = $this->leaveBalanceService->createLeaveRequest(
                $request,
                $context,
                $daysRequested
            );

            $this->logger->info('Leave application approved', [
                'leave_request_id' => $result['leaveRequestId'],
                'employee_id' => $request->employeeId,
            ]);

            return new LeaveApplicationResult(
                success: true,
                leaveRequestId: $result['leaveRequestId'],
                newBalance: $result['newBalance'],
                message: 'Leave application approved',
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to create leave request', [
                'employee_id' => $request->employeeId,
                'error' => $e->getMessage(),
            ]);

            return new LeaveApplicationResult(
                success: false,
                message: 'Failed to create leave request: ' . $e->getMessage(),
            );
        }
    }

    /**
     * @return array<string>
     */
    public function getSupportedOperations(): array
    {
        return ['apply_leave', 'approve_leave', 'cancel_leave', 'recalculate_balance'];
    }
}
