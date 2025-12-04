<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Coordinators;

use Nexus\HumanResourceOperations\DTOs\HiringRequest;
use Nexus\HumanResourceOperations\DTOs\HiringResult;
use Nexus\HumanResourceOperations\DataProviders\RecruitmentDataProvider;
use Nexus\HumanResourceOperations\Services\HiringRuleRegistry;
use Nexus\HumanResourceOperations\Services\EmployeeRegistrationService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for hiring operations.
 * 
 * Following Advanced Orchestrator Pattern:
 * - Coordinators are Traffic Cops, not Workers
 * - Accept Request DTO, return Result DTO
 * - Call DataProvider to get context
 * - Call RuleRegistry to validate
 * - Call Service to execute business logic
 * - NO complex if/else, NO calculations, NO direct repository queries
 */
final readonly class HiringCoordinator
{
    public function __construct(
        private RecruitmentDataProvider $dataProvider,
        private HiringRuleRegistry $ruleRegistry,
        private EmployeeRegistrationService $registrationService,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Process hiring decision for a candidate.
     * 
     * Flow:
     * 1. Get application context from DataProvider
     * 2. Validate readiness using Rules
     * 3. If hired and valid, execute registration via Service
     * 4. Return structured result
     */
    public function processHiringDecision(HiringRequest $request): HiringResult
    {
        $this->logger->info('Processing hiring decision', [
            'application_id' => $request->applicationId,
            'hired' => $request->hired,
        ]);

        // Step 1: Get application context (Data Provider aggregates cross-package data)
        $context = $this->dataProvider->getApplicationContext($request->applicationId);

        // Step 2: If not hired, just record decision and return
        if (!$request->hired) {
            $this->logger->info('Candidate not hired', [
                'application_id' => $request->applicationId,
            ]);

            return new HiringResult(
                success: true,
                message: 'Candidate rejected',
            );
        }

        // Step 3: Validate readiness for hiring (Rule Engine)
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
            $this->logger->warning('Hiring validation failed', [
                'application_id' => $request->applicationId,
                'issues' => $issues,
            ]);

            return new HiringResult(
                success: false,
                message: 'Candidate does not meet hiring requirements',
                issues: $issues,
            );
        }

        // Step 4: Execute employee registration (Service handles complex logic)
        try {
            $employee = $this->registrationService->registerNewEmployee(
                applicationId: $request->applicationId,
                candidateName: $context->candidateName,
                candidateEmail: $context->candidateEmail,
                positionId: $request->positionId ?? 'default',
                departmentId: $request->departmentId ?? $context->departmentId,
                startDate: $request->startDate ?? date('Y-m-d'),
                reportsTo: $request->reportsTo,
                metadata: $request->metadata ?? [],
            );

            $this->logger->info('Employee successfully registered', [
                'employee_id' => $employee['employeeId'],
                'user_id' => $employee['userId'],
            ]);

            return new HiringResult(
                success: true,
                employeeId: $employee['employeeId'],
                userId: $employee['userId'],
                message: 'Employee successfully hired and registered',
            );

        } catch (\Throwable $e) {
            $this->logger->error('Employee registration failed', [
                'application_id' => $request->applicationId,
                'error' => $e->getMessage(),
            ]);

            return new HiringResult(
                success: false,
                message: 'Failed to register employee: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Get supported operations.
     * 
     * @return array<string>
     */
    public function getSupportedOperations(): array
    {
        return ['process_hiring_decision', 'validate_hiring_readiness'];
    }
}
