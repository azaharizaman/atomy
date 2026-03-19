<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project as ProjectModel;
use App\Models\ProjectAcl;
use App\Models\Rfq;
use App\Models\Task as TaskModel;
use App\Services\Project\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Nexus\Project\Enums\ProjectStatus;
use Nexus\Project\ValueObjects\ProjectSummary;
use Nexus\ProjectManagementOperations\ProjectManagementOperationsCoordinator;

final class ProjectController extends Controller
{
    private const ACL_ROLES = ['owner', 'manager', 'contributor', 'viewer', 'client_stakeholder'];

    public function __construct(
        private readonly ProjectService $projects,
        private readonly ProjectManagementOperationsCoordinator $healthCoordinator,
    ) {
    }

    private function assertFeatureEnabled(): void
    {
        if (! config('features.projects')) {
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

    private function assertProjectOwnedByTenant(Request $request, string $projectId): void
    {
        $tenantId = $this->tenantId($request);

        $exists = ProjectModel::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $projectId)
            ->exists();

        if (! $exists) {
            abort(404);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $this->assertFeatureEnabled();
        $tenantId = $this->tenantId($request);

        $items = ProjectModel::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (ProjectModel $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'status' => $p->status,
                'client_id' => $p->client_id,
                'start_date' => $p->start_date?->format(DATE_ATOM),
                'end_date' => $p->end_date?->format(DATE_ATOM),
            ]);

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->assertFeatureEnabled();
        $this->tenantId($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'project_manager_id' => 'required|string',
        ]);

        $id = (string) \Illuminate\Support\Str::ulid();
        $summary = new ProjectSummary(
            id: $id,
            name: $validated['name'],
            clientId: $validated['client_id'],
            startDate: new \DateTimeImmutable($validated['start_date']),
            endDate: new \DateTimeImmutable($validated['end_date']),
            projectManagerId: $validated['project_manager_id'],
            status: ProjectStatus::Planning,
        );
        $this->projects->create($summary);

        $project = $this->projects->findById($id);
        if ($project === null) {
            abort(500, 'Project creation failed');
        }

        return response()->json([
            'data' => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status->value,
                'client_id' => $project->clientId,
                'start_date' => $project->startDate->format(DATE_ATOM),
                'end_date' => $project->endDate->format(DATE_ATOM),
            ],
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $this->assertFeatureEnabled();
        $this->tenantId($request);
        $this->assertProjectOwnedByTenant($request, $id);

        $project = $this->projects->findById($id);

        if ($project === null) {
            abort(404);
        }

        return response()->json([
            'data' => [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status->value,
                'client_id' => $project->clientId,
                'start_date' => $project->startDate->format(DATE_ATOM),
                'end_date' => $project->endDate->format(DATE_ATOM),
                'budget_type' => $project->budgetType,
                'completion_percentage' => $project->completionPercentage,
            ],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->assertFeatureEnabled();
        $this->tenantId($request);
        $this->assertProjectOwnedByTenant($request, $id);
        $project = $this->projects->findById($id);
        if ($project === null) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'client_id' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'project_manager_id' => 'sometimes|string',
            'budget_type' => 'sometimes|string',
            'completion_percentage' => 'sometimes|numeric|min:0|max:100',
        ]);

        $effectiveStart = isset($validated['start_date'])
            ? new \DateTimeImmutable($validated['start_date'])
            : $project->startDate;
        $effectiveEnd = isset($validated['end_date'])
            ? new \DateTimeImmutable($validated['end_date'])
            : $project->endDate;
        if ($effectiveEnd < $effectiveStart) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'end_date' => ['The end date must be on or after the start date.'],
            ]);
        }

        $summary = new ProjectSummary(
            id: $id,
            name: $validated['name'] ?? $project->name,
            clientId: $validated['client_id'] ?? $project->clientId,
            startDate: $effectiveStart,
            endDate: $effectiveEnd,
            projectManagerId: $validated['project_manager_id'] ?? $project->projectManagerId,
            status: $project->status,
            budgetType: $validated['budget_type'] ?? $project->budgetType,
            completionPercentage: (float) ($validated['completion_percentage'] ?? $project->completionPercentage),
        );
        $this->projects->update($summary);

        $updated = $this->projects->findById($id);
        if ($updated === null) {
            abort(404);
        }
        return response()->json([
            'data' => [
                'id' => $updated->id,
                'name' => $updated->name,
                'status' => $updated->status->value,
                'client_id' => $updated->clientId,
                'start_date' => $updated->startDate->format(DATE_ATOM),
                'end_date' => $updated->endDate->format(DATE_ATOM),
            ],
        ]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $this->assertFeatureEnabled();
        $this->tenantId($request);
        $this->assertProjectOwnedByTenant($request, $id);
        $project = $this->projects->findById($id);
        if ($project === null) {
            abort(404);
        }

        $validated = $request->validate(['status' => 'required|string|in:planning,active,on_hold,completed,cancelled']);
        $status = ProjectStatus::from($validated['status']);

        $summary = new ProjectSummary(
            id: $id,
            name: $project->name,
            clientId: $project->clientId,
            startDate: $project->startDate,
            endDate: $project->endDate,
            projectManagerId: $project->projectManagerId,
            status: $status,
            budgetType: $project->budgetType,
            completionPercentage: $project->completionPercentage,
        );
        $this->projects->update($summary);

        $updated = $this->projects->findById($id);
        if ($updated === null) {
            abort(404);
        }
        return response()->json([
            'data' => [
                'id' => $updated->id,
                'status' => $updated->status->value,
            ],
        ]);
    }

    public function health(Request $request, string $id): JsonResponse
    {
        $this->assertFeatureEnabled();
        $tenantId = $this->tenantId($request);

        $this->assertProjectOwnedByTenant($request, $id);

        $health = $this->healthCoordinator->getFullHealth($tenantId, $id);

        return response()->json([
            'data' => [
                'project_id' => $id,
                'overall_score' => $health->overallScore,
                'labor' => [
                    'actual_hours' => $health->laborHealth->actualHours,
                    'health_percentage' => $health->laborHealth->healthPercentage,
                ],
                'expense' => [
                    'health_percentage' => $health->expenseHealth->healthPercentage,
                ],
                'timeline' => [
                    'completion_percentage' => $health->timelineHealth->completionPercentage,
                    'total_milestones' => $health->timelineHealth->totalMilestones,
                    'completed_milestones' => $health->timelineHealth->completedMilestones,
                ],
            ],
        ]);
    }

    public function rfqs(Request $request, string $id): JsonResponse
    {
        $this->assertFeatureEnabled();
        $tenantId = $this->tenantId($request);
        $this->assertProjectOwnedByTenant($request, $id);
        $items = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where('project_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Rfq $r) => [
                'id' => $r->id,
                'rfq_number' => $r->rfq_number,
                'title' => $r->title,
                'status' => $r->status,
            ]);

        return response()->json(['data' => $items]);
    }

    public function tasks(Request $request, string $id): JsonResponse
    {
        $this->assertFeatureEnabled();
        $tenantId = $this->tenantId($request);
        $this->assertProjectOwnedByTenant($request, $id);
        $items = TaskModel::query()
            ->where('tenant_id', $tenantId)
            ->where('project_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (TaskModel $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'status' => $t->status,
                'due_date' => $t->due_date?->format(DATE_ATOM),
            ]);

        return response()->json(['data' => $items]);
    }

    public function budget(Request $request, string $id): JsonResponse
    {
        $this->assertFeatureEnabled();
        $tenantId = $this->tenantId($request);
        $this->assertProjectOwnedByTenant($request, $id);

        $rfqIds = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where('project_id', $id)
            ->pluck('id')
            ->all();

        $budgeted = (float) Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where('project_id', $id)
            ->sum('estimated_value');

        $actual = 0.0;
        $currency = 'USD';

        if (count($rfqIds) > 0) {
            $actual = (float) DB::table('awards')
                ->where('tenant_id', $tenantId)
                ->whereIn('rfq_id', $rfqIds)
                ->where('status', 'signed_off')
                ->sum('amount');

            $currencyRow = DB::table('awards')
                ->where('tenant_id', $tenantId)
                ->whereIn('rfq_id', $rfqIds)
                ->where('status', 'signed_off')
                ->select(['currency'])
                ->orderByDesc('created_at')
                ->first();

            if ($currencyRow !== null && isset($currencyRow->currency) && is_string($currencyRow->currency) && $currencyRow->currency !== '') {
                $currency = $currencyRow->currency;
            }
        }

        return response()->json([
            'data' => [
                'project_id' => $id,
                'budgeted' => $budgeted,
                'actual' => $actual,
                'currency' => $currency,
            ],
        ]);
    }

    public function getAcl(Request $request, string $id): JsonResponse
    {
        $this->assertFeatureEnabled();
        $tenantId = $this->tenantId($request);
        $this->assertProjectOwnedByTenant($request, $id);
        if ($this->projects->findById($id) === null) {
            abort(404);
        }
        $rows = ProjectAcl::query()
            ->where('tenant_id', $tenantId)
            ->where('project_id', $id)
            ->get(['user_id', 'role'])
            ->map(fn (ProjectAcl $row) => ['user_id' => $row->user_id, 'role' => $row->role])
            ->values()
            ->all();
        return response()->json(['data' => ['roles' => $rows]]);
    }

    public function updateAcl(Request $request, string $id): JsonResponse
    {
        $this->assertFeatureEnabled();
        $tenantId = $this->tenantId($request);
        $this->assertProjectOwnedByTenant($request, $id);
        if ($this->projects->findById($id) === null) {
            abort(404);
        }
        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*.user_id' => ['required', 'string', 'distinct', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'roles.*.role' => ['required', 'string', Rule::in(self::ACL_ROLES)],
        ]);
        DB::transaction(function () use ($id, $tenantId, $validated): void {
            ProjectAcl::query()
                ->where('tenant_id', $tenantId)
                ->where('project_id', $id)
                ->delete();
            foreach ($validated['roles'] as $entry) {
                ProjectAcl::query()->create([
                    'project_id' => $id,
                    'user_id' => $entry['user_id'],
                    'role' => $entry['role'],
                    'tenant_id' => $tenantId,
                ]);
            }
        });
        $rows = ProjectAcl::query()
            ->where('tenant_id', $tenantId)
            ->where('project_id', $id)
            ->get(['user_id', 'role'])
            ->map(fn (ProjectAcl $row) => ['user_id' => $row->user_id, 'role' => $row->role])
            ->values()
            ->all();
        return response()->json(['data' => ['roles' => $rows]]);
    }
}

