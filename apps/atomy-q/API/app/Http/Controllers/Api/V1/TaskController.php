<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Task as TaskModel;
use App\Services\Project\ProjectAclService;
use App\Services\Task\TaskService;
use App\Services\Task\TaskTenantLinkService;
use App\Services\Task\Exceptions\TaskNotFoundException;
use Nexus\Task\Contracts\TaskQueryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Nexus\Task\Exceptions\CircularDependencyException;
use DomainException;
use Nexus\Task\Enums\TaskPriority;
use Nexus\Task\Enums\TaskStatus;
use Nexus\Task\ValueObjects\TaskSummary;

final class TaskController extends Controller
{
    private readonly TaskService $tasks;
    private readonly TaskQueryInterface $taskQuery;
    private readonly TaskTenantLinkService $taskTenantLink;

    public function __construct(
        TaskService $tasks,
        TaskQueryInterface $taskQuery,
        TaskTenantLinkService $taskTenantLink,
        private readonly ProjectAclService $projectAcl,
    ) {
        $this->tasks = $tasks;
        $this->taskQuery = $taskQuery;
        $this->taskTenantLink = $taskTenantLink;
    }

    private function userId(Request $request): string
    {
        return (string) $request->attributes->get('auth_user_id', '');
    }

    private function assertProjectAclWhenProjectSet(Request $request, ?string $projectId): void
    {
        if ($projectId === null || $projectId === '') {
            return;
        }
        $tenantId = $this->tenantId($request);
        $userId = $this->userId($request);
        if ($userId === '' || ! $this->projectAcl->userCanAccessProject($tenantId, $userId, $projectId)) {
            abort(403, 'Access denied: project permission required');
        }
    }

    private function assertFeatureEnabled(): void
    {
        if (! config('features.tasks')) {
            abort(404);
        }
    }

    private function tenantId(Request $request): string
    {
        $id = (string) $request->attributes->get('auth_tenant_id', '');
        if ($id === '') {
            abort(403, 'Tenant context required');
        }
        return $id;
    }

    private function assertTaskOwnedByTenant(Request $request, string $taskId): void
    {
        $tenantId = $this->tenantId($request);

        $exists = TaskModel::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $taskId)
            ->exists();

        if (! $exists) {
            abort(404);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $this->assertFeatureEnabled();
        $tenantId = $this->tenantId($request);

        $assigneeId = $request->query('assignee_id');
        $query = TaskModel::query()->where('tenant_id', $tenantId);
        if ($assigneeId !== null && $assigneeId !== '') {
            $query->whereJsonContains('assignee_ids', $assigneeId);
        }
        $items = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (TaskModel $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'status' => $t->status,
                'due_date' => $t->due_date?->format(DATE_ATOM),
                'project_id' => $t->project_id,
            ]);

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->assertFeatureEnabled();
        $tenantId = $this->tenantId($request);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'sometimes|string',
            'project_id' => ['sometimes', 'nullable', Rule::exists('projects', 'id')->where('tenant_id', $tenantId)],
            'assignee_ids' => 'sometimes|array',
            'assignee_ids.*' => 'string',
            'priority' => 'sometimes|string|in:low,medium,high,critical',
            'due_date' => 'sometimes|nullable|date',
        ]);

        $id = (string) \Illuminate\Support\Str::ulid();
        $task = new TaskSummary(
            id: $id,
            title: $validated['title'],
            description: $validated['description'] ?? '',
            status: TaskStatus::Pending,
            priority: TaskPriority::from($validated['priority'] ?? 'medium'),
            dueDate: isset($validated['due_date']) ? new \DateTimeImmutable($validated['due_date']) : null,
            assigneeIds: $validated['assignee_ids'] ?? [],
            predecessorIds: [],
        );
        $projectId = $validated['project_id'] ?? null;

        try {
            $created = DB::transaction(function () use ($task, $tenantId, $id, $projectId) {
                $this->tasks->create($task, []);
                $created = $this->tasks->findById($id);
                if ($created === null) {
                    throw new \RuntimeException('Task creation failed');
                }
                $this->taskTenantLink->setTaskProjectId($tenantId, $id, $projectId);
                return $created;
            });
        } catch (TaskNotFoundException) {
            abort(404);
        } catch (DomainException $e) {
            return response()->json(['errors' => ['project_id' => [$e->getMessage()]]], 422);
        } catch (\RuntimeException $e) {
            abort(500, $e->getMessage());
        }

        return response()->json([
            'data' => [
                'id' => $created->id,
                'title' => $created->title,
                'status' => $created->status->value,
                'assignee_ids' => $created->assigneeIds,
                'due_date' => $created->dueDate?->format(DATE_ATOM),
            ],
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $this->assertFeatureEnabled();
        $tenantId = $this->tenantId($request);
        $this->assertTaskOwnedByTenant($request, $id);

        $task = $this->tasks->findById($id);
        if ($task === null) {
            abort(404);
        }

        $model = TaskModel::query()->where('tenant_id', $tenantId)->where('id', $id)->first();
        if ($model === null) {
            abort(404);
        }
        $this->assertProjectAclWhenProjectSet($request, $model->project_id);

        return response()->json([
            'data' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status->value,
                'priority' => $task->priority->value,
                'assignee_ids' => $task->assigneeIds,
                'predecessor_ids' => $task->predecessorIds,
                'due_date' => $task->dueDate?->format(DATE_ATOM),
                'completed_at' => $task->completedAt?->format(DATE_ATOM),
                'project_id' => $model->project_id,
            ],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->assertFeatureEnabled();
        $tenantId = $this->tenantId($request);
        $this->assertTaskOwnedByTenant($request, $id);
        $taskModel = TaskModel::query()->where('tenant_id', $tenantId)->where('id', $id)->first();
        if ($taskModel !== null) {
            $this->assertProjectAclWhenProjectSet($request, $taskModel->project_id);
        }
        $task = $this->tasks->findById($id);
        if ($task === null) {
            abort(404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'priority' => 'sometimes|string|in:low,medium,high,critical',
            'due_date' => 'sometimes|nullable|date',
            'assignee_ids' => 'sometimes|array',
            'assignee_ids.*' => 'string',
        ]);

        if (array_key_exists('title', $validated) && trim((string) $validated['title']) === '') {
            throw ValidationException::withMessages(['title' => 'The title must not be empty.']);
        }

        $dueDate = array_key_exists('due_date', $validated)
            ? ($validated['due_date'] !== null && $validated['due_date'] !== '' ? new \DateTimeImmutable($validated['due_date']) : null)
            : $task->dueDate;

        $updated = new TaskSummary(
            id: $id,
            title: $validated['title'] ?? $task->title,
            description: $validated['description'] ?? $task->description,
            status: $task->status,
            priority: isset($validated['priority']) ? TaskPriority::from($validated['priority']) : $task->priority,
            dueDate: $dueDate,
            assigneeIds: $validated['assignee_ids'] ?? $task->assigneeIds,
            predecessorIds: $task->predecessorIds,
            completedAt: $task->completedAt,
        );
        $this->tasks->update($updated, []);

        $result = $this->tasks->findById($id);
        if ($result === null) {
            abort(404);
        }
        return response()->json([
            'data' => [
                'id' => $result->id,
                'title' => $result->title,
                'status' => $result->status->value,
            ],
        ]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $this->assertFeatureEnabled();
        $tenantId = $this->tenantId($request);
        $this->assertTaskOwnedByTenant($request, $id);
        $taskModel = TaskModel::query()->where('tenant_id', $tenantId)->where('id', $id)->first();
        if ($taskModel !== null) {
            $this->assertProjectAclWhenProjectSet($request, $taskModel->project_id);
        }
        $task = $this->tasks->findById($id);
        if ($task === null) {
            abort(404);
        }

        $validated = $request->validate(['status' => 'required|string|in:pending,in_progress,completed,cancelled']);
        $status = TaskStatus::from($validated['status']);

        $completedAt = $status === TaskStatus::Completed ? new \DateTimeImmutable('now') : null;

        $updated = new TaskSummary(
            id: $id,
            title: $task->title,
            description: $task->description,
            status: $status,
            priority: $task->priority,
            dueDate: $task->dueDate,
            assigneeIds: $task->assigneeIds,
            predecessorIds: $task->predecessorIds,
            completedAt: $completedAt,
        );
        $this->tasks->update($updated, []);

        $result = $this->tasks->findById($id);
        if ($result === null) {
            abort(404);
        }
        return response()->json([
            'data' => [
                'id' => $result->id,
                'status' => $result->status->value,
            ],
        ]);
    }

    public function getDependencies(Request $request, string $id): JsonResponse
    {
        $this->assertFeatureEnabled();
        $tenantId = $this->tenantId($request);
        $this->assertTaskOwnedByTenant($request, $id);
        $task = $this->tasks->findById($id);
        if ($task === null) {
            abort(404);
        }
        $predecessors = $this->taskQuery->getPredecessorIds($id);
        return response()->json([
            'data' => [
                'task_id' => $id,
                'predecessor_ids' => $predecessors,
            ],
        ]);
    }

    public function updateDependencies(Request $request, string $id): JsonResponse
    {
        $this->assertFeatureEnabled();
        $tenantId = $this->tenantId($request);
        $this->assertTaskOwnedByTenant($request, $id);
        $task = $this->tasks->findById($id);
        if ($task === null) {
            abort(404);
        }

        $validated = $request->validate([
            'predecessor_ids' => 'present|array',
            'predecessor_ids.*' => 'string',
        ]);
        $predecessorIds = array_values($validated['predecessor_ids']);

        $all = TaskModel::query()->where('tenant_id', $tenantId)->get();
        $tenantTaskIds = $all->pluck('id')->all();
        foreach ($predecessorIds as $pid) {
            if (! in_array($pid, $tenantTaskIds, true)) {
                return response()->json(['errors' => ['predecessor_ids' => ['Contains unknown task id for this tenant.']]], 422);
            }
        }
        $graph = [];
        foreach ($all as $t) {
            $graph[$t->id] = $t->predecessor_ids ?? [];
        }
        $graph[$id] = $predecessorIds;
        try {
            $this->tasks->validateDependencies($graph);
        } catch (CircularDependencyException $e) {
            return response()->json(['errors' => ['predecessor_ids' => [$e->getMessage()]]], 422);
        }

        TaskModel::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->update(['predecessor_ids' => $predecessorIds]);

        return response()->json([
            'data' => [
                'task_id' => $id,
                'predecessor_ids' => $predecessorIds,
            ],
        ]);
    }

    public function schedulePreview(Request $request): JsonResponse
    {
        $this->assertFeatureEnabled();
        $this->tenantId($request);
        return response()->json([
            'data' => [
                'message' => 'Schedule preview (planned). Use ScheduleCalculatorInterface.',
            ],
        ]);
    }
}
