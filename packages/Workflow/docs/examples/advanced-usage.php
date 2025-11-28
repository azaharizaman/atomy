<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Workflow
 *
 * This example demonstrates:
 * 1. Multi-approver workflows with different strategies
 * 2. Guard conditions with complex expressions
 * 3. SLA tracking and escalation
 * 4. Delegation chains
 * 5. Workflow data manipulation
 *
 * @package Nexus\Workflow
 */

use Nexus\Workflow\Contracts\WorkflowInterface;
use Nexus\Workflow\Services\WorkflowManager;
use Nexus\Workflow\Services\TaskManager;
use Nexus\Workflow\Services\SlaService;
use Nexus\Workflow\Services\EscalationService;
use Nexus\Workflow\Services\DelegationService;
use Nexus\Workflow\Services\InboxService;
use Nexus\Workflow\ValueObjects\ApprovalStrategy;
use Nexus\Workflow\ValueObjects\TaskAction;
use Nexus\Workflow\ValueObjects\SlaStatus;
use Nexus\Workflow\ValueObjects\SlaConfiguration;
use Nexus\Workflow\ValueObjects\EscalationRule;

// ============================================
// Example 1: Multi-Approver Workflow
// ============================================

/**
 * Complex approval workflow with tiered approvers
 */
$enterpriseApprovalWorkflow = [
    'name' => 'enterprise_purchase_approval',
    'states' => [
        'draft',
        'pending_manager',
        'pending_director',
        'pending_finance',
        'approved',
        'rejected',
    ],
    'transitions' => [
        [
            'name' => 'submit',
            'from' => ['draft'],
            'to' => 'pending_manager',
        ],
        // Under $10k - manager can approve directly
        [
            'name' => 'manager_approve',
            'from' => ['pending_manager'],
            'to' => 'approved',
            'guard' => 'amount <= 10000',
        ],
        // $10k-$50k needs director
        [
            'name' => 'escalate_director',
            'from' => ['pending_manager'],
            'to' => 'pending_director',
            'guard' => 'amount > 10000',
        ],
        // Over $50k needs finance too
        [
            'name' => 'escalate_finance',
            'from' => ['pending_director'],
            'to' => 'pending_finance',
            'guard' => 'amount > 50000',
        ],
        [
            'name' => 'director_approve',
            'from' => ['pending_director'],
            'to' => 'approved',
            'guard' => 'amount <= 50000',
        ],
        [
            'name' => 'finance_approve',
            'from' => ['pending_finance'],
            'to' => 'approved',
        ],
        [
            'name' => 'reject',
            'from' => ['pending_manager', 'pending_director', 'pending_finance'],
            'to' => 'rejected',
        ],
    ],
    'config' => [
        'pending_director' => [
            'approval_strategy' => ApprovalStrategy::MAJORITY->value,
            'required_approvers' => 2,
        ],
        'pending_finance' => [
            'approval_strategy' => ApprovalStrategy::UNISON->value,
            'required_approvers' => 3, // CFO, Controller, VP Finance
        ],
    ],
];

// ============================================
// Example 2: Multi-Approver Resolution
// ============================================

/**
 * Service handling multi-approver workflows
 */
final readonly class MultiApproverService
{
    public function __construct(
        private WorkflowManager $workflowManager,
        private TaskManager $taskManager
    ) {}

    /**
     * Create tasks for multiple approvers
     */
    public function createApprovalTasks(
        string $workflowId,
        string $stateName,
        array $approverIds,
        ApprovalStrategy $strategy
    ): void {
        foreach ($approverIds as $approverId) {
            $this->taskManager->createTask(
                workflowId: $workflowId,
                stateName: $stateName,
                title: 'Multi-Approval Required',
                assignedUserId: $approverId,
                priority: 'high'
            );
        }

        // Store strategy in workflow data
        $workflow = $this->getWorkflow($workflowId);
        $data = $workflow->getData();
        $data['approval_strategy'] = $strategy->value;
        $data['pending_approvals'] = count($approverIds);
        $data['received_approvals'] = 0;
        $workflow->setData($data);
    }

    /**
     * Process an approval and check if threshold is met
     */
    public function processApproval(
        string $taskId,
        string $userId,
        TaskAction $action
    ): bool {
        $task = $this->taskManager->completeTask($taskId, $userId, $action);
        
        $workflow = $this->getWorkflow($task->getWorkflowId());
        $data = $workflow->getData();
        $strategy = ApprovalStrategy::from($data['approval_strategy']);

        if ($action === TaskAction::APPROVE) {
            $data['received_approvals']++;
        }

        $workflow->setData($data);

        return $this->isThresholdMet($strategy, $data);
    }

    private function isThresholdMet(ApprovalStrategy $strategy, array $data): bool
    {
        $received = $data['received_approvals'];
        $pending = $data['pending_approvals'];

        return match ($strategy) {
            ApprovalStrategy::UNISON => $received === $pending,
            ApprovalStrategy::MAJORITY => $received > ($pending / 2),
            ApprovalStrategy::FIRST => $received >= 1,
            ApprovalStrategy::QUORUM => $received >= ($data['quorum_threshold'] ?? 2),
            ApprovalStrategy::WEIGHTED => $this->checkWeightedThreshold($data),
        };
    }

    private function checkWeightedThreshold(array $data): bool
    {
        $weight = $data['approval_weight'] ?? 0;
        $threshold = $data['weight_threshold'] ?? 100;
        return $weight >= $threshold;
    }

    private function getWorkflow(string $id): WorkflowInterface
    {
        // Would use repository in real implementation
        throw new \RuntimeException('Implementation required');
    }
}

// ============================================
// Example 3: SLA Tracking
// ============================================

/**
 * SLA monitoring service
 */
final readonly class SlaMonitoringService
{
    public function __construct(
        private SlaService $slaService,
        private WorkflowManager $workflowManager
    ) {}

    /**
     * Configure SLA for a workflow
     */
    public function configureSla(string $workflowId): void
    {
        // Track SLA with 24h warning, 48h breach
        $this->slaService->trackSla($workflowId);
    }

    /**
     * Check SLA status and take action
     */
    public function checkAndEscalate(string $workflowId): void
    {
        $status = $this->slaService->getSlaStatus($workflowId);

        match ($status) {
            SlaStatus::ON_TRACK => $this->logStatus($workflowId, 'on_track'),
            SlaStatus::AT_RISK => $this->sendReminder($workflowId),
            SlaStatus::BREACHED => $this->escalateToManager($workflowId),
        };
    }

    private function sendReminder(string $workflowId): void
    {
        // Send notification to assignees
        echo "Sending SLA reminder for workflow: {$workflowId}\n";
    }

    private function escalateToManager(string $workflowId): void
    {
        // Notify manager of SLA breach
        echo "SLA breached! Escalating workflow: {$workflowId}\n";
    }

    private function logStatus(string $workflowId, string $status): void
    {
        echo "Workflow {$workflowId} SLA status: {$status}\n";
    }
}

// ============================================
// Example 4: Escalation Rules
// ============================================

/**
 * Escalation configuration and processing
 */
final readonly class EscalationHandler
{
    public function __construct(
        private EscalationService $escalationService,
        private TaskManager $taskManager
    ) {}

    /**
     * Configure escalation rules for workflow states
     */
    public function configureRules(): void
    {
        // After 4 hours, reassign to team lead
        $this->escalationService->defineRule(
            stateName: 'pending_manager',
            thresholdHours: 4,
            action: 'reassign_team_lead'
        );

        // After 8 hours, notify director
        $this->escalationService->defineRule(
            stateName: 'pending_manager',
            thresholdHours: 8,
            action: 'notify_director'
        );

        // After 24 hours, auto-approve low-value items
        $this->escalationService->defineRule(
            stateName: 'pending_manager',
            thresholdHours: 24,
            action: 'auto_approve_if_low_value'
        );
    }

    /**
     * Process escalations (run from scheduler)
     */
    public function processEscalations(): void
    {
        $this->escalationService->processEscalations();
    }
}

// ============================================
// Example 5: Delegation
// ============================================

/**
 * Task delegation with vacation coverage
 */
final readonly class DelegationHandler
{
    public function __construct(
        private DelegationService $delegationService,
        private TaskManager $taskManager
    ) {}

    /**
     * Set up vacation delegation
     */
    public function setupVacationCoverage(
        string $userId,
        string $delegateeId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): void {
        $this->delegationService->createDelegation(
            delegatorId: $userId,
            delegateeId: $delegateeId,
            startDate: $startDate,
            endDate: $endDate
        );
    }

    /**
     * Get effective assignee (following delegation chain)
     */
    public function getEffectiveAssignee(string $userId): string
    {
        $chain = $this->delegationService->getDelegationChain($userId);
        
        if (empty($chain)) {
            return $userId;
        }

        // Return the last person in the chain
        return end($chain)->getDelegateeId();
    }

    /**
     * Delegate a specific task
     */
    public function delegateTask(
        string $taskId,
        string $fromUserId,
        string $toUserId
    ): void {
        $this->taskManager->delegateTask($taskId, $fromUserId, $toUserId);
    }
}

// ============================================
// Example 6: User Inbox
// ============================================

/**
 * Task inbox with filtering
 */
final readonly class TaskInboxService
{
    public function __construct(
        private InboxService $inboxService
    ) {}

    /**
     * Get tasks for dashboard
     */
    public function getDashboardTasks(string $userId): array
    {
        $allTasks = $this->inboxService->forUser($userId);
        
        return [
            'urgent' => array_filter($allTasks, fn($t) => $t->getPriority() === 'urgent'),
            'overdue' => array_filter($allTasks, fn($t) => 
                $t->getDueAt() && $t->getDueAt() < new \DateTimeImmutable()
            ),
            'today' => array_filter($allTasks, fn($t) => 
                $t->getDueAt()?->format('Y-m-d') === date('Y-m-d')
            ),
            'pending' => $this->inboxService->pending(),
        ];
    }

    /**
     * Filter tasks by criteria
     */
    public function filterTasks(array $criteria): array
    {
        return $this->inboxService->filter($criteria);
    }
}

// ============================================
// Example 7: Workflow with Compensation
// ============================================

/**
 * Workflow that rolls back on failure
 */
final readonly class CompensatingWorkflowService
{
    public function __construct(
        private WorkflowManager $workflowManager
    ) {}

    /**
     * Execute multi-step process with compensation
     */
    public function executeWithCompensation(
        string $workflowId,
        array $steps
    ): void {
        $completedSteps = [];

        try {
            foreach ($steps as $step) {
                $this->workflowManager->apply(
                    workflowId: $workflowId,
                    transitionName: $step['transition'],
                    actorId: $step['actor']
                );
                $completedSteps[] = $step;
            }
        } catch (\Exception $e) {
            // Compensate in reverse order
            foreach (array_reverse($completedSteps) as $step) {
                if (isset($step['compensation'])) {
                    $this->workflowManager->apply(
                        workflowId: $workflowId,
                        transitionName: $step['compensation'],
                        actorId: 'system',
                        comment: 'Compensation for: ' . $e->getMessage()
                    );
                }
            }
            throw $e;
        }
    }
}

// ============================================
// Example 8: Workflow Locking
// ============================================

/**
 * Prevent concurrent modifications
 */
final readonly class ConcurrencyService
{
    public function __construct(
        private WorkflowManager $workflowManager
    ) {}

    /**
     * Execute transition with lock
     */
    public function safeTransition(
        string $workflowId,
        string $transition,
        string $userId
    ): void {
        // Lock workflow
        $this->workflowManager->lock($workflowId);

        try {
            // Check and apply
            if ($this->workflowManager->can($workflowId, $transition)) {
                $this->workflowManager->apply($workflowId, $transition, $userId);
            }
        } finally {
            // Always unlock
            $this->workflowManager->unlock($workflowId);
        }
    }
}

// ============================================
// Example 9: Scheduled Processing (Cron Job)
// ============================================

/**
 * Scheduler command for workflow maintenance
 *
 * Add to Laravel scheduler:
 * $schedule->command('workflow:process')->everyMinute();
 */
final class WorkflowSchedulerCommand
{
    public function __construct(
        private SlaMonitoringService $slaService,
        private EscalationHandler $escalationHandler
    ) {}

    public function handle(): void
    {
        // Process SLA breaches
        foreach ($this->getActiveWorkflows() as $workflowId) {
            $this->slaService->checkAndEscalate($workflowId);
        }

        // Process escalations
        $this->escalationHandler->processEscalations();

        echo "Workflow processing complete\n";
    }

    private function getActiveWorkflows(): array
    {
        // Return active workflow IDs from database
        return [];
    }
}

// ============================================
// Usage Summary
// ============================================

/*
Key patterns demonstrated:

1. Multi-Approver Strategies:
   - UNISON: All must approve
   - MAJORITY: >50% must approve
   - FIRST: Any one can approve
   - QUORUM: N of M must approve
   - WEIGHTED: Vote weights sum to threshold

2. SLA Tracking:
   - Warning threshold (e.g., 24 hours)
   - Breach threshold (e.g., 48 hours)
   - Automatic notifications

3. Escalation:
   - Time-based rules
   - Automatic reassignment
   - Manager notifications

4. Delegation:
   - Date-ranged delegation
   - Chain limits (max 3 levels)
   - Automatic task routing

5. Concurrency:
   - Workflow locking
   - Safe transitions
   - Compensation on failure

6. Scheduled Processing:
   - Timer-based transitions
   - SLA monitoring
   - Escalation processing
*/
