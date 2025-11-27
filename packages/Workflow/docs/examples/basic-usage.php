<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Workflow
 *
 * This example demonstrates:
 * 1. Creating a workflow instance
 * 2. Applying transitions
 * 3. Checking if transitions are allowed
 * 4. Creating and completing tasks
 *
 * @package Nexus\Workflow
 */

use Nexus\Workflow\Contracts\WorkflowInterface;
use Nexus\Workflow\Contracts\WorkflowRepositoryInterface;
use Nexus\Workflow\Contracts\DefinitionRepositoryInterface;
use Nexus\Workflow\Services\WorkflowManager;
use Nexus\Workflow\Services\TaskManager;
use Nexus\Workflow\ValueObjects\TaskAction;

// ============================================
// Step 1: Setup (in your service provider)
// ============================================

/**
 * Your service provider binds interfaces to implementations:
 *
 * $this->app->singleton(WorkflowRepositoryInterface::class, EloquentWorkflowRepository::class);
 * $this->app->singleton(DefinitionRepositoryInterface::class, EloquentDefinitionRepository::class);
 * $this->app->singleton(WorkflowManager::class);
 * $this->app->singleton(TaskManager::class);
 */

// ============================================
// Step 2: Define a Workflow Definition
// ============================================

/**
 * Workflow definitions are typically stored in database.
 * This is the JSON structure:
 */
$purchaseOrderWorkflow = [
    'name' => 'purchase_order_approval',
    'states' => [
        'draft',
        'pending_approval',
        'approved',
        'rejected',
    ],
    'transitions' => [
        [
            'name' => 'submit',
            'from' => ['draft'],
            'to' => 'pending_approval',
        ],
        [
            'name' => 'approve',
            'from' => ['pending_approval'],
            'to' => 'approved',
            'guard' => 'amount <= 10000', // Only auto-approve under $10k
        ],
        [
            'name' => 'reject',
            'from' => ['pending_approval'],
            'to' => 'rejected',
        ],
        [
            'name' => 'revise',
            'from' => ['rejected'],
            'to' => 'draft',
        ],
    ],
];

// ============================================
// Step 3: Start a Workflow
// ============================================

/**
 * Controller or service that starts a workflow
 */
final readonly class PurchaseOrderService
{
    public function __construct(
        private WorkflowManager $workflowManager,
        private TaskManager $taskManager
    ) {}

    /**
     * Submit a purchase order for approval
     */
    public function submitForApproval(
        string $purchaseOrderId,
        float $amount,
        string $submitterId
    ): WorkflowInterface {
        // Get definition ID (from config or lookup)
        $definitionId = 'def_po_approval_01';

        // Create workflow instance
        $workflow = $this->workflowManager->instantiate(
            definitionId: $definitionId,
            subjectType: 'App\\Models\\PurchaseOrder',
            subjectId: $purchaseOrderId,
            data: [
                'amount' => $amount,
                'department' => 'Engineering',
                'submitter_id' => $submitterId,
            ]
        );

        // Apply 'submit' transition
        $this->workflowManager->apply(
            workflowId: $workflow->getId(),
            transitionName: 'submit',
            actorId: $submitterId,
            comment: 'Submitted for approval'
        );

        return $workflow;
    }
}

// ============================================
// Step 4: Check Transition Availability
// ============================================

/**
 * Check if user can approve/reject
 */
final readonly class ApprovalChecker
{
    public function __construct(
        private WorkflowManager $workflowManager
    ) {}

    public function getAvailableActions(string $workflowId): array
    {
        $actions = [];

        if ($this->workflowManager->can($workflowId, 'approve')) {
            $actions[] = 'approve';
        }

        if ($this->workflowManager->can($workflowId, 'reject')) {
            $actions[] = 'reject';
        }

        if ($this->workflowManager->can($workflowId, 'revise')) {
            $actions[] = 'revise';
        }

        return $actions;
    }
}

// ============================================
// Step 5: Create and Complete Tasks
// ============================================

/**
 * Task-based approval workflow
 */
final readonly class TaskApprovalService
{
    public function __construct(
        private WorkflowManager $workflowManager,
        private TaskManager $taskManager
    ) {}

    /**
     * Create approval task when workflow enters pending state
     */
    public function createApprovalTask(
        string $workflowId,
        string $title,
        string $approverId
    ): void {
        $this->taskManager->createTask(
            workflowId: $workflowId,
            stateName: 'pending_approval',
            title: $title,
            assignedUserId: $approverId,
            description: 'Please review and approve or reject this request.',
            priority: 'high',
            dueAt: new \DateTimeImmutable('+2 days')
        );
    }

    /**
     * Handle approval action
     */
    public function approve(
        string $taskId,
        string $userId,
        string $comment
    ): void {
        // Complete the task
        $task = $this->taskManager->completeTask(
            taskId: $taskId,
            userId: $userId,
            action: TaskAction::APPROVE,
            comment: $comment
        );

        // Apply the transition to workflow
        $this->workflowManager->apply(
            workflowId: $task->getWorkflowId(),
            transitionName: 'approve',
            actorId: $userId,
            comment: $comment
        );
    }

    /**
     * Handle rejection action
     */
    public function reject(
        string $taskId,
        string $userId,
        string $reason
    ): void {
        // Complete the task
        $task = $this->taskManager->completeTask(
            taskId: $taskId,
            userId: $userId,
            action: TaskAction::REJECT,
            comment: $reason
        );

        // Apply the transition to workflow
        $this->workflowManager->apply(
            workflowId: $task->getWorkflowId(),
            transitionName: 'reject',
            actorId: $userId,
            comment: $reason
        );
    }
}

// ============================================
// Step 6: Get Workflow History
// ============================================

/**
 * Retrieve audit trail
 */
final readonly class WorkflowAuditService
{
    public function __construct(
        private WorkflowManager $workflowManager
    ) {}

    public function getAuditTrail(string $workflowId): array
    {
        $history = $this->workflowManager->history($workflowId);

        return array_map(fn($entry) => [
            'from_state' => $entry->getFromState(),
            'to_state' => $entry->getToState(),
            'transition' => $entry->getTransitionName(),
            'actor' => $entry->getActorId(),
            'comment' => $entry->getComment(),
            'timestamp' => $entry->getCreatedAt()->format('Y-m-d H:i:s'),
        ], $history);
    }
}

// ============================================
// Usage Example (in a controller)
// ============================================

/*
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderService $poService,
        private readonly TaskApprovalService $approvalService
    ) {}

    public function submit(Request $request, string $id)
    {
        $workflow = $this->poService->submitForApproval(
            purchaseOrderId: $id,
            amount: $request->input('amount'),
            submitterId: auth()->id()
        );

        return response()->json([
            'message' => 'Submitted for approval',
            'workflow_id' => $workflow->getId(),
            'state' => $workflow->getCurrentState(),
        ]);
    }

    public function approve(Request $request, string $taskId)
    {
        $this->approvalService->approve(
            taskId: $taskId,
            userId: auth()->id(),
            comment: $request->input('comment', 'Approved')
        );

        return response()->json(['message' => 'Approved']);
    }
}
*/

// Expected Output:
// After submit: state = "pending_approval"
// After approve: state = "approved"
// After reject: state = "rejected"
