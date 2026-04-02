<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Idempotency\IdempotencyCompletion;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\RfqBulkActionRequest;
use App\Http\Requests\RfqDraftRequest;
use App\Http\Requests\RfqStatusTransitionRequest;
use App\Http\Resources\RfqResource;
use App\Models\Approval;
use App\Models\ComparisonRun;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\QuoteSubmission;
use App\Models\VendorInvitation;
use App\Services\Project\ProjectAclService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Nexus\Idempotency\Contracts\IdempotencyServiceInterface;
use Nexus\Sourcing\Exceptions\InvalidRfqStatusTransitionException;
use Nexus\Sourcing\Exceptions\RfqLifecyclePreconditionException;
use Nexus\Sourcing\Exceptions\UnsupportedRfqBulkActionException;
use Nexus\SourcingOperations\Contracts\RfqLifecycleCoordinatorInterface;
use Nexus\SourcingOperations\DTOs\ApplyRfqBulkActionCommand;
use Nexus\SourcingOperations\DTOs\DuplicateRfqCommand;
use Nexus\SourcingOperations\DTOs\SaveRfqDraftCommand;
use Nexus\SourcingOperations\DTOs\TransitionRfqStatusCommand;

final class RfqController extends Controller
{
    use ExtractsAuthContext;

    public function __construct(
        private readonly ProjectAclService $projectAcl,
        private readonly RfqLifecycleCoordinatorInterface $rfqLifecycle,
    ) {
    }

    private function assertProjectAclWhenProjectSet(Request $request, ?string $projectId): void
    {
        if ($projectId === null || $projectId === '') {
            return;
        }
        $tenantId = $this->tenantId($request);
        $userId = $this->userId($request);
        if (! $this->projectAcl->userCanViewProject($tenantId, $userId, $projectId)) {
            abort(404, 'Not found');
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rfqValidationRules(string $tenantId, bool $forUpdate = false): array
    {
        $titleRule = $forUpdate ? ['sometimes', 'filled', 'string', 'max:255'] : ['required', 'string', 'max:255'];
        $submissionDeadlineRule = $forUpdate
            ? ['sometimes', 'required', 'date']
            : ['required', 'date'];

        return [
            'title' => $titleRule,
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:64'],
            'department' => ['nullable', 'string', 'max:64'],
            'project_id' => ['nullable', Rule::exists('projects', 'id')->where('tenant_id', $tenantId)],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'savings_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'submission_deadline' => $submissionDeadlineRule,
            'closing_date' => ['nullable', 'date'],
            'expected_award_at' => ['nullable', 'date'],
            'technical_review_due_at' => ['nullable', 'date'],
            'financial_review_due_at' => ['nullable', 'date'],
            'payment_terms' => ['nullable', 'string', 'max:64'],
            'evaluation_method' => ['nullable', 'string', 'max:64'],
        ];
    }

    private function assertClosingOnOrAfterSubmission(Carbon $submissionDeadline, ?Carbon $closingDate): void
    {
        if ($closingDate === null) {
            return;
        }
        if ($closingDate->lt($submissionDeadline)) {
            throw ValidationException::withMessages([
                'closing_date' => ['The closing date must be on or after the submission deadline.'],
            ]);
        }
    }

    private function findTenantScopedRfq(string $tenantId, string $id): ?Rfq
    {
        return Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($id): void {
                $builder->where('id', $id)->orWhere('rfq_number', $id);
            })
            ->first();
    }

    private function loadResourceRfq(string $tenantId, string $rfqId): ?Rfq
    {
        return Rfq::query()
            ->with(['project' => static function ($builder) use ($tenantId): void {
                $builder->select(['id', 'name', 'tenant_id'])->where('tenant_id', $tenantId);
            }])
            ->where('tenant_id', $tenantId)
            ->where('id', $rfqId)
            ->first();
    }

    private function resourceResponse(Rfq $rfq, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => (new RfqResource($rfq))->toArray(request()),
        ], $status);
    }

    private function lifecyclePreconditionResponse(RfqLifecyclePreconditionException $exception): JsonResponse
    {
        $message = $exception->getMessage();
        $notFound = str_contains($message, 'could not be found')
            || str_contains($message, 'was not found');

        if ($notFound) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        return response()->json([
            'error' => 'Validation failed',
            'details' => [
                'rfq' => [$message],
            ],
        ], 422);
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $params = $this->paginationParams($request);

        $query = Rfq::query()
            ->with('owner:id,name,email')
            ->withCount([
                'vendorInvitations as vendors_count',
                'quoteSubmissions as quotes_count',
            ])
            ->where('tenant_id', $tenantId);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($ownerId = $request->query('ownerId')) {
            $query->where('owner_id', $ownerId);
        } elseif ($owner = $request->query('owner')) {
            $query->whereHas('owner', function ($builder) use ($owner): void {
                $builder
                    ->where('name', 'ilike', "%{$owner}%")
                    ->orWhere('email', 'ilike', "%{$owner}%");
            });
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($projectId = $request->query('project_id')) {
            $this->assertProjectAclWhenProjectSet($request, (string) $projectId);
            $query->where('project_id', $projectId);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('rfq_number', 'ilike', "%{$search}%")
                    ->orWhere('title', 'ilike', "%{$search}%");
            });
        }

        $sortField = (string) ($request->query('sortBy') ?? $request->query('sort') ?? 'created_at');
        $direction = strtolower((string) ($request->query('direction') ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortable = [
            'created_at' => 'created_at',
            'deadline' => 'submission_deadline',
            'submission_deadline' => 'submission_deadline',
            'estimated_value' => 'estimated_value',
            'title' => 'title',
            'status' => 'status',
        ];
        $orderBy = $sortable[$sortField] ?? 'created_at';

        $paginator = $query->orderBy($orderBy, $direction)
            ->paginate($params['per_page'], ['*'], 'page', $params['page']);

        $rows = $paginator->getCollection()->map(static function (Rfq $rfq): array {
            $savings = $rfq->savings_percentage !== null ? rtrim(rtrim((string) $rfq->savings_percentage, '0'), '.') . '%' : null;

            return [
                'id' => $rfq->id,
                'rfq_number' => $rfq->rfq_number,
                'title' => $rfq->title,
                'status' => $rfq->status,
                'project_id' => $rfq->project_id,
                'owner' => $rfq->owner ? [
                    'id' => $rfq->owner->id,
                    'name' => $rfq->owner->name,
                    'email' => $rfq->owner->email,
                ] : null,
                'deadline' => optional($rfq->submission_deadline)->toAtomString(),
                'category' => $rfq->category,
                'estimated_value' => $rfq->estimated_value,
                'estValue' => $rfq->estimated_value,
                'savings_percentage' => $rfq->savings_percentage,
                'savings' => $savings,
                'vendors_count' => $rfq->vendors_count,
                'quotes_count' => $rfq->quotes_count,
            ];
        })->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $params['page'],
                'per_page' => $params['per_page'],
                'total' => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * GET /rfqs/counts — tenant-scoped RFQ counts for dashboard nav badges.
     *
     * Keys align with WEB sidebar filters: active ≈ published, archived ≈ cancelled.
     */
    public function counts(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $byStatus = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $draft = (int) ($byStatus['draft'] ?? 0);
        $published = (int) ($byStatus['published'] ?? 0);
        $closed = (int) ($byStatus['closed'] ?? 0);
        $awarded = (int) ($byStatus['awarded'] ?? 0);
        $cancelled = (int) ($byStatus['cancelled'] ?? 0);

        return response()->json([
            'data' => [
                'draft' => $draft,
                'published' => $published,
                'closed' => $closed,
                'awarded' => $awarded,
                'cancelled' => $cancelled,
                'active' => $published,
                'pending' => 0,
                'archived' => $cancelled,
            ],
        ]);
    }

    public function store(Request $request, IdempotencyServiceInterface $idempotency): JsonResponse
    {
        try {
            $tenantId = $this->tenantId($request);
            $ownerId = $this->userId($request);

            $validated = $request->validate($this->rfqValidationRules($tenantId, false));

            $nextSeq = (int) Rfq::query()
                ->where('tenant_id', $tenantId)
                ->where('rfq_number', 'like', 'RFQ-%')
                ->count() + 1;
            $rfqNumber = sprintf('RFQ-%s-%04d', date('Y'), $nextSeq);

            $rfq = new Rfq();
            $rfq->tenant_id = $tenantId;
            $rfq->owner_id = $ownerId;
            $rfq->rfq_number = $rfqNumber;
            $rfq->title = (string) $validated['title'];
            $rfq->description = $validated['description'] ?? null;
            $rfq->category = $validated['category'] ?? null;
            $rfq->department = $validated['department'] ?? null;
            $rfq->project_id = $validated['project_id'] ?? null;
            $rfq->status = 'draft';
            $rfq->estimated_value = isset($validated['estimated_value']) ? (float) $validated['estimated_value'] : 0.0;
            $rfq->savings_percentage = isset($validated['savings_percentage']) ? (float) $validated['savings_percentage'] : 0.0;
            $submission = Carbon::parse((string) $validated['submission_deadline']);
            $closing = ! empty($validated['closing_date']) ? Carbon::parse((string) $validated['closing_date']) : null;
            $this->assertClosingOnOrAfterSubmission($submission, $closing);
            $rfq->submission_deadline = $submission;
            $rfq->closing_date = $closing;
            $rfq->payment_terms = $validated['payment_terms'] ?? null;
            $rfq->evaluation_method = $validated['evaluation_method'] ?? null;
            $rfq->save();

            $response = response()->json([
                'data' => [
                    'id' => $rfq->id,
                    'rfq_number' => $rfq->rfq_number,
                    'title' => $rfq->title,
                    'status' => $rfq->status,
                    'project_id' => $rfq->project_id,
                ],
            ], 201);

            return IdempotencyCompletion::succeed($request, $idempotency, $response);
        } catch (ValidationException $e) {
            IdempotencyCompletion::fail($request, $idempotency);
            throw $e;
        } catch (\Throwable $e) {
            IdempotencyCompletion::fail($request, $idempotency);
            throw $e;
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        /** @var Rfq|null $rfq */
        $rfq = Rfq::query()
            ->with('owner:id,name,email')
            ->with(['project' => static function ($builder) use ($tenantId): void {
                $builder->select(['id', 'name', 'tenant_id'])->where('tenant_id', $tenantId);
            }])
            ->withCount([
                'vendorInvitations as vendors_count',
                'quoteSubmissions as quotes_count',
            ])
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($id): void {
                $builder
                    ->where('id', $id)
                    ->orWhere('rfq_number', $id);
            })
            ->first();

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }
        $this->assertProjectAclWhenProjectSet($request, $rfq->project_id);

        $savings = $rfq->savings_percentage !== null ? rtrim(rtrim((string) $rfq->savings_percentage, '0'), '.') . '%' : null;
        $projectName = $rfq->project?->name;

        return response()->json([
            'data' => [
                'id' => $rfq->id,
                'rfq_number' => $rfq->rfq_number,
                'title' => $rfq->title,
                'description' => $rfq->description,
                'status' => $rfq->status,
                'project_id' => $rfq->project_id,
                'project_name' => $projectName,
                'owner' => $rfq->owner ? [
                    'id' => $rfq->owner->id,
                    'name' => $rfq->owner->name,
                    'email' => $rfq->owner->email,
                ] : null,
                'deadline' => optional($rfq->submission_deadline)->toAtomString(),
                'submission_deadline' => optional($rfq->submission_deadline)->toAtomString(),
                'closing_date' => optional($rfq->closing_date)->toAtomString(),
                'expected_award_at' => optional($rfq->expected_award_at)->toAtomString(),
                'technical_review_due_at' => optional($rfq->technical_review_due_at)->toAtomString(),
                'financial_review_due_at' => optional($rfq->financial_review_due_at)->toAtomString(),
                'category' => $rfq->category,
                'estimated_value' => $rfq->estimated_value,
                'estValue' => $rfq->estimated_value,
                'savings_percentage' => $rfq->savings_percentage,
                'savings' => $savings,
                'vendors_count' => $rfq->vendors_count,
                'quotes_count' => $rfq->quotes_count,
            ],
        ]);
    }

    /**
     * GET /rfqs/:id/overview — aggregated summary for the RFQ overview screen.
     */
    public function overview(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $rfq = Rfq::query()
            ->with('owner:id,name,email')
            ->withCount([
                'vendorInvitations as vendors_count',
                'quoteSubmissions as quotes_count',
            ])
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($id): void {
                $builder->where('id', $id)->orWhere('rfq_number', $id);
            })
            ->first();

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }
        $this->assertProjectAclWhenProjectSet($request, $rfq->project_id);

        $savings = $rfq->savings_percentage !== null ? rtrim(rtrim((string) $rfq->savings_percentage, '0'), '.') . '%' : null;

        $quotesTotal = (int) $rfq->quotes_count;
        $uploadedCount = QuoteSubmission::query()
            ->where('rfq_id', $rfq->id)
            ->where('tenant_id', $tenantId)
            ->where('status', 'uploaded')
            ->count();
        $needsReviewCount = QuoteSubmission::query()
            ->where('rfq_id', $rfq->id)
            ->where('tenant_id', $tenantId)
            ->where('status', 'needs_review')
            ->count();
        $readyCount = QuoteSubmission::query()
            ->where('rfq_id', $rfq->id)
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['accepted', 'ready'])
            ->count();
        $acceptedCount = QuoteSubmission::query()
            ->where('rfq_id', $rfq->id)
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['accepted', 'ready'])
            ->count();
        $normProgress = $quotesTotal > 0 ? (int) round($acceptedCount / $quotesTotal * 100) : 0;

        $latestRun = ComparisonRun::query()
            ->where('rfq_id', $rfq->id)
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->first();

        $comparison = null;
        if ($latestRun !== null) {
            $comparison = [
                'id' => $latestRun->id,
                'name' => $latestRun->name ?? 'Run',
                'status' => $latestRun->status,
                'is_preview' => (bool) ($latestRun->is_preview ?? true),
                'created_at' => $latestRun->created_at?->toAtomString(),
            ];
        }

        $approvalsPending = Approval::query()->where('rfq_id', $rfq->id)->where('tenant_id', $tenantId)->where('status', 'pending')->count();
        $approvalsApproved = Approval::query()->where('rfq_id', $rfq->id)->where('tenant_id', $tenantId)->where('status', 'approved')->count();
        $approvalsRejected = Approval::query()->where('rfq_id', $rfq->id)->where('tenant_id', $tenantId)->where('status', 'rejected')->count();
        $approvalOverall = $approvalsPending > 0 ? 'pending' : ($approvalsApproved > 0 ? 'approved' : ($approvalsRejected > 0 ? 'rejected' : 'none'));

        $activity = $this->buildOverviewActivity($tenantId, $rfq, 20);
        $expectedQuotes = (int) $rfq->vendors_count;

        $latestComparisonRun = null;
        if ($comparison !== null) {
            $latestComparisonRun = [
                'id' => $comparison['id'],
                'mode' => ($comparison['is_preview'] ?? true) ? 'preview' : 'final',
                'status' => $comparison['status'],
            ];
        }

        return response()->json([
            'data' => [
                'rfq' => [
                    'id' => $rfq->id,
                    'rfq_number' => $rfq->rfq_number,
                    'title' => $rfq->title,
                    'description' => $rfq->description,
                    'status' => $rfq->status,
                    'owner' => $rfq->owner ? [
                        'id' => $rfq->owner->id,
                        'name' => $rfq->owner->name,
                        'email' => $rfq->owner->email,
                    ] : null,
                    'submission_deadline' => optional($rfq->submission_deadline)->toAtomString(),
                    'closing_date' => optional($rfq->closing_date)->toAtomString(),
                    'expected_award_at' => optional($rfq->expected_award_at)->toAtomString(),
                    'technical_review_due_at' => optional($rfq->technical_review_due_at)->toAtomString(),
                    'financial_review_due_at' => optional($rfq->financial_review_due_at)->toAtomString(),
                    'category' => $rfq->category,
                    'estimated_value' => $rfq->estimated_value,
                    'estValue' => $rfq->estimated_value,
                    'savings_percentage' => $rfq->savings_percentage,
                    'savings' => $savings,
                    'vendors_count' => $rfq->vendors_count,
                    'quotes_count' => $rfq->quotes_count,
                ],
                'expected_quotes' => $expectedQuotes,
                'normalization' => [
                    'uploaded_count' => $uploadedCount,
                    'needs_review_count' => $needsReviewCount,
                    'ready_count' => $readyCount,
                    'accepted_count' => $acceptedCount,
                    'total_quotes' => $quotesTotal,
                    'progress_pct' => $normProgress,
                ],
                'comparison' => $comparison,
                'approvals' => [
                    'pending_count' => $approvalsPending,
                    'approved_count' => $approvalsApproved,
                    'rejected_count' => $approvalsRejected,
                    'overall' => $approvalOverall,
                ],
                'activity' => $activity,
                'expectedQuotes' => $expectedQuotes,
                'normalizationProgress' => $normProgress,
                'latestComparisonRun' => $latestComparisonRun,
                'approvalStatus' => [
                    'overall' => $approvalOverall,
                    'pending_count' => $approvalsPending,
                    'approved_count' => $approvalsApproved,
                    'rejected_count' => $approvalsRejected,
                ],
            ],
        ]);
    }

    /**
     * GET /rfqs/:rfqId/activity?limit=20 — tenant-scoped activity feed (lighter than full overview).
     *
     * @queryParam limit int 1–50, default 20
     */
    public function activity(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $limit = $this->clampActivityLimit((int) $request->query('limit', 20));

        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($rfqId): void {
                $builder->where('id', $rfqId)->orWhere('rfq_number', $rfqId);
            })
            ->first();

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }
        $this->assertProjectAclWhenProjectSet($request, $rfq->project_id);

        $events = $this->buildOverviewActivity($tenantId, $rfq, $limit);

        return response()->json([
            'data' => $events,
            'meta' => [
                'limit' => $limit,
                'rfq_id' => $rfq->id,
            ],
        ]);
    }

    private function clampActivityLimit(int $limit): int
    {
        return max(1, min(50, $limit));
    }

    /**
     * Split a total activity cap across N sources (remainder distributed to earlier sources).
     *
     * @return list<int>
     */
    private function distributeActivityPerSourceLimits(int $limit, int $sourceCount): array
    {
        $limit = $this->clampActivityLimit($limit);
        if ($sourceCount < 1) {
            return [];
        }

        $base = intdiv($limit, $sourceCount);
        $remainder = $limit % $sourceCount;
        $limits = [];
        for ($i = 0; $i < $sourceCount; $i++) {
            $limits[] = $base + ($i < $remainder ? 1 : 0);
        }

        return $limits;
    }

    /**
     * @return list<array{id: string, type: string, actor: string, action: string, timestamp: string}>
     */
    private function buildOverviewActivity(string $tenantId, Rfq $rfq, int $limit = 20): array
    {
        $limit = $this->clampActivityLimit($limit);
        [$lInv, $lQs, $lRun, $lApp] = $this->distributeActivityPerSourceLimits($limit, 4);

        $events = [];

        foreach (VendorInvitation::query()->where('rfq_id', $rfq->id)->where('tenant_id', $tenantId)->whereNotNull('invited_at')->orderByDesc('invited_at')->limit($lInv)->get() as $inv) {
            $events[] = [
                'id' => 'inv-' . $inv->id,
                'type' => 'invitation',
                'actor' => 'System',
                'action' => 'Invitation sent to ' . ($inv->vendor_name ?? $inv->vendor_email),
                'timestamp' => $inv->invited_at instanceof \DateTimeInterface ? Carbon::instance($inv->invited_at)->toAtomString() : '',
            ];
        }

        foreach (QuoteSubmission::query()->where('rfq_id', $rfq->id)->where('tenant_id', $tenantId)->orderByDesc('submitted_at')->orderByDesc('created_at')->limit($lQs)->get() as $qs) {
            $submittedAt = $qs->submitted_at ?? $qs->created_at;
            $statusLabel = $this->quoteSubmissionStatusLabel((string) $qs->status);
            $events[] = [
                'id' => 'qs-' . $qs->id,
                'type' => 'quote',
                'actor' => $qs->vendor_name ?? 'Vendor',
                'action' => 'Quote submitted' . ($statusLabel !== null ? ' (' . $statusLabel . ')' : ''),
                'timestamp' => $submittedAt instanceof \DateTimeInterface ? Carbon::instance($submittedAt)->toAtomString() : $qs->created_at?->toAtomString() ?? '',
            ];
        }

        foreach (ComparisonRun::query()->where('rfq_id', $rfq->id)->where('tenant_id', $tenantId)->orderByDesc('created_at')->limit($lRun)->get() as $run) {
            $events[] = [
                'id' => 'run-' . $run->id,
                'type' => 'comparison',
                'actor' => 'System',
                'action' => 'Comparison run: ' . ($run->name ?? 'Run') . ' (' . $run->status . ')',
                'timestamp' => $run->created_at?->toAtomString() ?? '',
            ];
        }

        foreach (Approval::query()->where('rfq_id', $rfq->id)->where('tenant_id', $tenantId)->orderByDesc('requested_at')->orderByDesc('created_at')->limit($lApp)->get() as $app) {
            $ts = $app->approved_at ?? $app->requested_at ?? $app->created_at;
            $events[] = [
                'id' => 'app-' . $app->id,
                'type' => 'approval',
                'actor' => 'System',
                'action' => 'Approval ' . $app->status,
                'timestamp' => $ts instanceof \DateTimeInterface ? Carbon::instance($ts)->toAtomString() : $app->created_at?->toAtomString() ?? '',
            ];
        }

        usort($events, static function (array $a, array $b): int {
            return strcmp($b['timestamp'], $a['timestamp']);
        });

        return array_slice($events, 0, $limit);
    }

    private function quoteSubmissionStatusLabel(string $status): ?string
    {
        return match ($status) {
            'accepted' => 'accepted',
            'ready' => 'ready',
            default => null,
        };
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($id): void {
                $builder->where('id', $id)->orWhere('rfq_number', $id);
            })
            ->firstOrFail();

        $this->assertProjectAclWhenProjectSet($request, $rfq->project_id);

        $data = $request->validate($this->rfqValidationRules($tenantId, true));

        if (array_key_exists('title', $data)) $rfq->title = (string) $data['title'];
        if (array_key_exists('description', $data)) $rfq->description = $data['description'];
        if (array_key_exists('category', $data)) $rfq->category = $data['category'];
        if (array_key_exists('department', $data)) $rfq->department = $data['department'];
        if (array_key_exists('project_id', $data)) $rfq->project_id = $data['project_id'] ?? null;
        if (array_key_exists('estimated_value', $data)) $rfq->estimated_value = $data['estimated_value'] !== null ? (float) $data['estimated_value'] : null;
        if (array_key_exists('savings_percentage', $data)) $rfq->savings_percentage = $data['savings_percentage'] !== null ? (float) $data['savings_percentage'] : null;
        if (array_key_exists('submission_deadline', $data)) {
            $rfq->submission_deadline = Carbon::parse((string) $data['submission_deadline']);
        }
        if (array_key_exists('closing_date', $data)) {
            $rfq->closing_date = $data['closing_date'] !== null && $data['closing_date'] !== ''
                ? Carbon::parse((string) $data['closing_date'])
                : null;
        }

        if ($rfq->submission_deadline === null) {
            throw ValidationException::withMessages([
                'submission_deadline' => ['The submission deadline field is required.'],
            ]);
        }

        $this->assertClosingOnOrAfterSubmission(
            Carbon::instance($rfq->submission_deadline),
            $rfq->closing_date !== null ? Carbon::instance($rfq->closing_date) : null,
        );
        if (array_key_exists('expected_award_at', $data)) $rfq->expected_award_at = $data['expected_award_at'] ? Carbon::parse($data['expected_award_at']) : null;
        if (array_key_exists('technical_review_due_at', $data)) $rfq->technical_review_due_at = $data['technical_review_due_at'] ? Carbon::parse($data['technical_review_due_at']) : null;
        if (array_key_exists('financial_review_due_at', $data)) $rfq->financial_review_due_at = $data['financial_review_due_at'] ? Carbon::parse($data['financial_review_due_at']) : null;
        if (array_key_exists('payment_terms', $data)) $rfq->payment_terms = $data['payment_terms'];
        if (array_key_exists('evaluation_method', $data)) $rfq->evaluation_method = $data['evaluation_method'];
        $rfq->save();

        return response()->json([
            'data' => [
                'id' => $rfq->id,
                'rfq_number' => $rfq->rfq_number,
                'title' => $rfq->title,
                'status' => $rfq->status,
                'project_id' => $rfq->project_id,
            ],
        ]);
    }

    public function updateStatus(RfqStatusTransitionRequest $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $rfq = $this->findTenantScopedRfq($tenantId, $id);

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }
        $this->assertProjectAclWhenProjectSet($request, $rfq->project_id);

        try {
            $outcome = $this->rfqLifecycle->transitionStatus(new TransitionRfqStatusCommand(
                tenantId: $tenantId,
                rfqId: (string) $rfq->id,
                status: (string) $request->validated('status'),
            ));
        } catch (RfqLifecyclePreconditionException $exception) {
            return $this->lifecyclePreconditionResponse($exception);
        } catch (InvalidRfqStatusTransitionException $exception) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => [
                    'status' => [$exception->getMessage()],
                ],
            ], 422);
        }

        $updated = $this->loadResourceRfq($tenantId, (string) $outcome->rfqId);

        if ($updated === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        return $this->resourceResponse($updated);
    }

    public function duplicate(Request $request, string $id, IdempotencyServiceInterface $idempotency): JsonResponse
    {
        try {
            $tenantId = $this->tenantId($request);
            $rfq = $this->findTenantScopedRfq($tenantId, $id);

            if ($rfq === null) {
                IdempotencyCompletion::fail($request, $idempotency);

                return response()->json(['message' => 'RFQ not found'], 404);
            }

            $this->assertProjectAclWhenProjectSet($request, $rfq->project_id);

            $outcome = $this->rfqLifecycle->duplicate(new DuplicateRfqCommand(
                tenantId: $tenantId,
                sourceRfqId: (string) $rfq->id,
            ));

            $duplicated = $this->loadResourceRfq($tenantId, (string) $outcome->rfqId);
            if ($duplicated === null) {
                IdempotencyCompletion::fail($request, $idempotency);

                return response()->json(['message' => 'RFQ not found'], 404);
            }

            $response = $this->resourceResponse($duplicated, 201);

            return IdempotencyCompletion::succeed($request, $idempotency, $response);
        } catch (RfqLifecyclePreconditionException $exception) {
            IdempotencyCompletion::fail($request, $idempotency);

            return $this->lifecyclePreconditionResponse($exception);
        } catch (\Throwable $e) {
            IdempotencyCompletion::fail($request, $idempotency);
            throw $e;
        }
    }

    public function saveDraft(RfqDraftRequest $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $rfq = $this->findTenantScopedRfq($tenantId, $id);

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $this->assertProjectAclWhenProjectSet($request, $rfq->project_id);

        $validated = $request->validated();

        if (array_key_exists('project_id', $validated)) {
            $this->assertProjectAclWhenProjectSet($request, is_string($validated['project_id'] ?? null) ? $validated['project_id'] : null);
        }

        $submissionDeadline = array_key_exists('submission_deadline', $validated)
            ? ($validated['submission_deadline'] !== null ? Carbon::parse((string) $validated['submission_deadline']) : null)
            : ($rfq->submission_deadline !== null ? Carbon::instance($rfq->submission_deadline) : null);
        $closingDate = array_key_exists('closing_date', $validated)
            ? (($validated['closing_date'] ?? null) !== null ? Carbon::parse((string) $validated['closing_date']) : null)
            : ($rfq->closing_date !== null ? Carbon::instance($rfq->closing_date) : null);

        if ($submissionDeadline !== null) {
            $this->assertClosingOnOrAfterSubmission($submissionDeadline, $closingDate);
        }

        try {
            $outcome = $this->rfqLifecycle->saveDraft(new SaveRfqDraftCommand(
                tenantId: $tenantId,
                rfqId: (string) $rfq->id,
                title: is_string($validated['title'] ?? null) ? $validated['title'] : null,
                description: is_string($validated['description'] ?? null) ? $validated['description'] : null,
                projectId: is_string($validated['project_id'] ?? null) ? $validated['project_id'] : null,
                estimatedValue: array_key_exists('estimated_value', $validated) && $validated['estimated_value'] !== null ? (float) $validated['estimated_value'] : null,
                savingsPercentage: array_key_exists('savings_percentage', $validated) && $validated['savings_percentage'] !== null ? (float) $validated['savings_percentage'] : null,
                submissionDeadline: $submissionDeadline?->toAtomString(),
                closingDate: $closingDate?->toAtomString(),
                expectedAwardAt: array_key_exists('expected_award_at', $validated) && $validated['expected_award_at'] !== null ? Carbon::parse((string) $validated['expected_award_at'])->toAtomString() : null,
                technicalReviewDueAt: array_key_exists('technical_review_due_at', $validated) && $validated['technical_review_due_at'] !== null ? Carbon::parse((string) $validated['technical_review_due_at'])->toAtomString() : null,
                financialReviewDueAt: array_key_exists('financial_review_due_at', $validated) && $validated['financial_review_due_at'] !== null ? Carbon::parse((string) $validated['financial_review_due_at'])->toAtomString() : null,
                paymentTerms: is_string($validated['payment_terms'] ?? null) ? $validated['payment_terms'] : null,
                evaluationMethod: is_string($validated['evaluation_method'] ?? null) ? $validated['evaluation_method'] : null,
            ));
        } catch (RfqLifecyclePreconditionException $exception) {
            return $this->lifecyclePreconditionResponse($exception);
        }

        $saved = $this->loadResourceRfq($tenantId, (string) $outcome->rfqId);

        if ($saved === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        return $this->resourceResponse($saved);
    }

    public function bulkAction(RfqBulkActionRequest $request, IdempotencyServiceInterface $idempotency): JsonResponse
    {
        try {
            $tenantId = $this->tenantId($request);
            /** @var array{action: string, rfq_ids: array<int, string>} $validated */
            $validated = $request->validated();

            $rfqs = Rfq::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $validated['rfq_ids'])
                ->get();

            if ($rfqs->count() !== count($validated['rfq_ids'])) {
                IdempotencyCompletion::fail($request, $idempotency);

                return response()->json(['message' => 'RFQ not found'], 404);
            }

            foreach ($rfqs as $rfq) {
                $this->assertProjectAclWhenProjectSet($request, $rfq->project_id);
            }

            $outcome = $this->rfqLifecycle->applyBulkAction(new ApplyRfqBulkActionCommand(
                tenantId: $tenantId,
                action: $validated['action'],
                rfqIds: $validated['rfq_ids'],
            ));

            $response = response()->json([
                'data' => [
                    'action' => $outcome->action,
                    'status' => $outcome->status,
                    'affected' => $outcome->affectedCount,
                    'rfq_ids' => $validated['rfq_ids'],
                ],
            ]);

            return IdempotencyCompletion::succeed($request, $idempotency, $response);
        } catch (UnsupportedRfqBulkActionException|RfqLifecyclePreconditionException|InvalidRfqStatusTransitionException $exception) {
            IdempotencyCompletion::fail($request, $idempotency);

            return response()->json([
                'error' => 'Validation failed',
                'details' => [
                    'action' => [$exception->getMessage()],
                ],
            ], 422);
        } catch (\Throwable $e) {
            IdempotencyCompletion::fail($request, $idempotency);
            throw $e;
        }
    }

    public function lineItems(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($rfqId): void {
                $builder->where('id', $rfqId)->orWhere('rfq_number', $rfqId);
            })
            ->first();

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $items = RfqLineItem::query()
            ->where('rfq_id', $rfq->id)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        $rows = $items->map(static function (RfqLineItem $item): array {
            return [
                'id' => $item->id,
                'rfq_id' => $item->rfq_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'uom' => $item->uom,
                'unit_price' => $item->unit_price,
                'currency' => $item->currency,
                'specifications' => $item->specifications,
                'sort_order' => $item->sort_order,
            ];
        })->values()->all();

        return response()->json(['data' => $rows]);
    }

    public function storeLineItem(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($rfqId): void {
                $builder->where('id', $rfqId)->orWhere('rfq_number', $rfqId);
            })
            ->first();

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'description' => ['required', 'string'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'uom' => ['required', 'string', 'max:32'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'specifications' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $maxOrder = (int) RfqLineItem::query()->where('rfq_id', $rfq->id)->max('sort_order');

        $item = new RfqLineItem();
        $item->tenant_id = $tenantId;
        $item->rfq_id = $rfq->id;
        $item->description = $request->input('description');
        $item->quantity = (float) $request->input('quantity');
        $item->uom = $request->input('uom');
        $item->unit_price = (float) $request->input('unit_price', 0);
        $item->currency = $request->input('currency', 'USD');
        $item->specifications = $request->input('specifications');
        $item->sort_order = $maxOrder + 1;
        $item->save();

        return response()->json([
            'data' => [
                'id' => $item->id,
                'rfq_id' => $item->rfq_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'uom' => $item->uom,
                'unit_price' => $item->unit_price,
                'currency' => $item->currency,
                'sort_order' => $item->sort_order,
            ],
        ], 201);
    }

    public function updateLineItem(Request $request, string $rfqId, string $itemId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($rfqId): void {
                $builder->where('id', $rfqId)->orWhere('rfq_number', $rfqId);
            })
            ->first();

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $item = RfqLineItem::query()
            ->where('rfq_id', $rfq->id)
            ->where('id', $itemId)
            ->first();

        if ($item === null) {
            return response()->json(['message' => 'Line item not found'], 404);
        }

        $fillable = ['description', 'quantity', 'uom', 'unit_price', 'currency', 'specifications', 'sort_order'];
        foreach ($fillable as $key) {
            if ($request->has($key)) {
                $item->{$key} = $key === 'quantity' || $key === 'unit_price' ? (float) $request->input($key) : $request->input($key);
                if ($key === 'sort_order') {
                    $item->sort_order = (int) $request->input($key);
                }
            }
        }
        $item->save();

        return response()->json([
            'data' => [
                'id' => $item->id,
                'rfq_id' => $item->rfq_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'uom' => $item->uom,
                'unit_price' => $item->unit_price,
                'currency' => $item->currency,
                'sort_order' => $item->sort_order,
            ],
        ]);
    }

    public function destroyLineItem(Request $request, string $rfqId, string $itemId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($rfqId): void {
                $builder->where('id', $rfqId)->orWhere('rfq_number', $rfqId);
            })
            ->first();

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $deleted = RfqLineItem::query()
            ->where('rfq_id', $rfq->id)
            ->where('id', $itemId)
            ->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'Line item not found'], 404);
        }

        return response()->json([], 204);
    }
}
