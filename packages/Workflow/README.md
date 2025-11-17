# Nexus\Workflow

**Framework-agnostic workflow engine for Nexus ERP monorepo**

## Overview

The Workflow package provides a powerful, flexible workflow engine that supports:

- **State Machine Management**: Define and execute finite state machines with guards and hooks
- **Task Management**: User task inbox with approval workflows
- **Conditional Routing**: Dynamic routing based on workflow data
- **Parallel Execution**: AND/OR gateways for complex approval flows
- **Multi-Approver Strategies**: Unison, majority, quorum, weighted, first-wins
- **Escalation & SLA**: Automatic escalations and SLA breach tracking
- **Delegation**: User delegation with date ranges and chain limits
- **Compensation Logic**: Rollback activities in reverse order on failure
- **Approval Matrix**: Threshold-based routing configuration
- **Event-Driven Timers**: Scalable timer system for scheduled actions
- **Plugin Architecture**: Extensible activities, conditions, strategies, and triggers

## Core Philosophy

This package is **framework-agnostic**. It defines **WHAT** workflows need to do (the logic), not **HOW** they are persisted or implemented. All database operations, Laravel-specific features, and Eloquent models belong in the `apps/Atomy` application layer.

## Architecture

```
packages/Workflow/
├── src/
│   ├── Contracts/          # Interfaces defining all dependencies
│   ├── Core/               # Internal workflow engine (state machine, task engine, etc.)
│   ├── Services/           # Public API (WorkflowManager, TaskManager, etc.)
│   ├── ValueObjects/       # Immutable data structures
│   └── Exceptions/         # Domain-specific exceptions
```

## Key Interfaces

### Workflow & State Management
- `WorkflowInterface` - Workflow instance representation
- `StateInterface` - Workflow state representation
- `TransitionInterface` - State transition representation
- `WorkflowRepositoryInterface` - Persistence contract

### Task Management
- `TaskInterface` - User task representation
- `TaskRepositoryInterface` - Task persistence contract
- `InboxInterface` - Task inbox query interface

### Execution & Control
- `StateEngineInterface` - State transition execution
- `TaskEngineInterface` - Task creation and completion
- `ConditionEvaluatorInterface` - Condition expression evaluation
- `ApprovalStrategyInterface` - Multi-approver logic

### Automation
- `EscalationInterface` - Escalation rule processing
- `SlaTrackerInterface` - SLA monitoring and breach detection
- `TimerInterface` - Timer management
- `DelegationInterface` - User delegation handling

### Extensibility
- `ActivityInterface` - Plugin activities (email, webhook, etc.)
- `TriggerInterface` - Workflow triggers (webhook, schedule, event)
- `StorageInterface` - Custom storage backends

## Value Objects

- `State` - Workflow state definition
- `Transition` - State transition definition
- `TaskAction` - Task action (approve, reject, request changes)
- `SlaStatus` - SLA tracking status (on_track, at_risk, breached)
- `ApprovalStrategy` - Multi-approver strategy enum
- `EscalationRule` - Escalation configuration
- `WorkflowData` - Typed workflow data container
- `ConditionExpression` - Parsed condition expression

## Services (Public API)

### WorkflowManager
Primary entry point for workflow operations:
- `instantiate(definition, data)` - Create new workflow instance
- `apply(workflow, transition)` - Execute state transition
- `can(workflow, transition)` - Check if transition is allowed
- `history(workflow)` - Retrieve state change history

### TaskManager
Task operations:
- `createTask(workflow, state, assignee)` - Create user task
- `completeTask(task, action, comment)` - Complete task with action
- `delegate(user, delegatee, dateRange)` - Delegate tasks

### InboxService
Task inbox queries:
- `forUser(userId)` - Get tasks for specific user
- `pending()` - Filter pending tasks
- `filter(criteria)` - Apply filters (workflow type, priority, due date)

### EscalationService
Escalation management:
- `processEscalations()` - Process overdue tasks
- `defineRule(state, threshold, action)` - Configure escalation rules

### SlaService
SLA tracking:
- `trackSla(workflow)` - Monitor SLA status
- `getSlaStatus(workflow)` - Get current SLA status
- `getBreaches()` - Retrieve breached workflows

### DelegationService
Delegation management:
- `createDelegation(delegator, delegatee, dateRange)` - Create delegation
- `revokeDelegation(delegation)` - Cancel delegation
- `getDelegationChain(user)` - Get delegation chain

## Exceptions

- `WorkflowNotFoundException` - Workflow not found
- `InvalidTransitionException` - Transition not allowed from current state
- `TaskNotFoundException` - Task not found
- `UnauthorizedTaskActionException` - User not authorized to act on task
- `InvalidWorkflowDefinitionException` - Invalid workflow structure
- `GuardConditionFailedException` - Transition guard condition failed
- `SlaBreachException` - SLA deadline breached
- `DelegationChainExceededException` - Delegation chain too deep (>3 levels)
- `CircularDependencyException` - Circular state dependency detected
- `InvalidConditionExpressionException` - Invalid condition syntax

## Usage Example (Conceptual)

```php
use Nexus\\Workflow\\Services\\WorkflowManager;

// Inject via constructor
public function __construct(
    private readonly WorkflowManager $workflowManager
) {}

// Check if transition is allowed
if ($this->workflowManager->can($workflow, 'approve')) {
    // Execute transition
    $this->workflowManager->apply($workflow, 'approve');
}

// Get history
$history = $this->workflowManager->history($workflow);
```

## Implementation

The consuming application (`apps/Atomy`) must provide:

1. **Eloquent Models** implementing workflow interfaces
2. **Repository Classes** implementing repository interfaces
3. **Database Migrations** for all workflow tables
4. **Service Provider** binding interfaces to implementations
5. **HasWorkflow Trait** for Eloquent models
6. **API Routes** exposing workflow functionality

See `apps/Atomy/app/` for concrete implementations.

## Requirements Coverage

This package fulfills requirements:
- ARC-WOR-0405 to ARC-WOR-0414 (Architectural requirements)
- BUS-WOR-0415 to BUS-WOR-0424 (Business requirements)
- FUN-WOR-0425 to FUN-WOR-0541 (Functional requirements)
- PERF-WOR-0543 to PERF-WOR-0547 (Performance requirements)
- SEC-WOR-0548 to SEC-WOR-0553 (Security requirements)
- REL-WOR-0554 to REL-WOR-0558 (Reliability requirements)
- SCL-WOR-0559 to SCL-WOR-0562 (Scalability requirements)
- MAINT-WOR-0563 to MAINT-WOR-0566 (Maintainability requirements)
- USE-WOR-0567 to USE-WOR-0584 (User stories)

## License

MIT License - see LICENSE file for details
