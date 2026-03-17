<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\ComparisonRun;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\QuoteSubmission;
use App\Models\VendorInvitation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class RfqController extends Controller
{
    use ExtractsAuthContext;

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rfqValidationRules(string $tenantId, bool $forUpdate = false): array
    {
        $titleRule = $forUpdate ? ['sometimes', 'filled', 'string', 'max:255'] : ['required', 'string', 'max:255'];
        return [
            'title' => $titleRule,
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:64'],
            'department' => ['nullable', 'string', 'max:64'],
            'project_id' => ['nullable', Rule::exists('projects', 'id')->where('tenant_id', $tenantId)],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'savings_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'submission_deadline' => ['nullable', 'date'],
            'closing_date' => ['nullable', 'date'],
            'payment_terms' => ['nullable', 'string', 'max:64'],
            'evaluation_method' => ['nullable', 'string', 'max:64'],
        ];
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

    public function store(Request $request): JsonResponse
    {
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
        $rfq->submission_deadline = ! empty($validated['submission_deadline']) ? Carbon::parse($validated['submission_deadline']) : null;
        $rfq->closing_date = ! empty($validated['closing_date']) ? Carbon::parse($validated['closing_date']) : null;
        $rfq->payment_terms = $validated['payment_terms'] ?? null;
        $rfq->evaluation_method = $validated['evaluation_method'] ?? null;
        $rfq->save();

        return response()->json([
            'data' => [
                'id' => $rfq->id,
                'rfq_number' => $rfq->rfq_number,
                'title' => $rfq->title,
                'status' => $rfq->status,
                'project_id' => $rfq->project_id,
            ],
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        /** @var Rfq|null $rfq */
        $rfq = Rfq::query()
            ->with('owner:id,name,email')
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

        $savings = $rfq->savings_percentage !== null ? rtrim(rtrim((string) $rfq->savings_percentage, '0'), '.') . '%' : null;

        return response()->json([
            'data' => [
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
                'submission_deadline' => optional($rfq->submission_deadline)->toAtomString(),
                'closing_date' => optional($rfq->closing_date)->toAtomString(),
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

        $savings = $rfq->savings_percentage !== null ? rtrim(rtrim((string) $rfq->savings_percentage, '0'), '.') . '%' : null;

        $quotesTotal = (int) $rfq->quotes_count;
        $acceptedCount = QuoteSubmission::query()
            ->where('rfq_id', $rfq->id)
            ->where('tenant_id', $tenantId)
            ->where('status', 'accepted')
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

        $activity = $this->buildOverviewActivity($tenantId, $rfq);
        $expectedQuotes = (int) $rfq->vendors_count;

        return response()->json([
            'data' => [
                'rfq' => [
                    'id' => $rfq->id,
                    'rfq_number' => $rfq->rfq_number,
                    'title' => $rfq->title,
                    'status' => $rfq->status,
                    'owner' => $rfq->owner ? [
                        'id' => $rfq->owner->id,
                        'name' => $rfq->owner->name,
                        'email' => $rfq->owner->email,
                    ] : null,
                    'submission_deadline' => optional($rfq->submission_deadline)->toAtomString(),
                    'closing_date' => optional($rfq->closing_date)->toAtomString(),
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
            ],
        ]);
    }

    /**
     * @return list<array{id: string, type: string, actor: string, action: string, timestamp: string}>
     */
    private function buildOverviewActivity(string $tenantId, Rfq $rfq): array
    {
        $events = [];

        foreach (VendorInvitation::query()->where('rfq_id', $rfq->id)->where('tenant_id', $tenantId)->whereNotNull('invited_at')->orderByDesc('invited_at')->limit(10)->get() as $inv) {
            $events[] = [
                'id' => 'inv-' . $inv->id,
                'type' => 'invitation',
                'actor' => 'System',
                'action' => 'Invitation sent to ' . ($inv->vendor_name ?? $inv->vendor_email),
                'timestamp' => $inv->invited_at instanceof \DateTimeInterface ? Carbon::instance($inv->invited_at)->toAtomString() : '',
            ];
        }

        foreach (QuoteSubmission::query()->where('rfq_id', $rfq->id)->where('tenant_id', $tenantId)->orderByDesc('submitted_at')->orderByDesc('created_at')->limit(10)->get() as $qs) {
            $submittedAt = $qs->submitted_at ?? $qs->created_at;
            $events[] = [
                'id' => 'qs-' . $qs->id,
                'type' => 'quote',
                'actor' => $qs->vendor_name ?? 'Vendor',
                'action' => 'Quote submitted' . ($qs->status === 'accepted' ? ' (accepted)' : ''),
                'timestamp' => $submittedAt instanceof \DateTimeInterface ? Carbon::instance($submittedAt)->toAtomString() : $qs->created_at?->toAtomString() ?? '',
            ];
        }

        foreach (ComparisonRun::query()->where('rfq_id', $rfq->id)->where('tenant_id', $tenantId)->orderByDesc('created_at')->limit(5)->get() as $run) {
            $events[] = [
                'id' => 'run-' . $run->id,
                'type' => 'comparison',
                'actor' => 'System',
                'action' => 'Comparison run: ' . ($run->name ?? 'Run') . ' (' . $run->status . ')',
                'timestamp' => $run->created_at?->toAtomString() ?? '',
            ];
        }

        foreach (Approval::query()->where('rfq_id', $rfq->id)->where('tenant_id', $tenantId)->orderByDesc('requested_at')->orderByDesc('created_at')->limit(5)->get() as $app) {
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

        return array_slice($events, 0, 20);
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

        $data = $request->validate($this->rfqValidationRules($tenantId, true));

        if (array_key_exists('title', $data)) $rfq->title = (string) $data['title'];
        if (array_key_exists('description', $data)) $rfq->description = $data['description'];
        if (array_key_exists('category', $data)) $rfq->category = $data['category'];
        if (array_key_exists('department', $data)) $rfq->department = $data['department'];
        if (array_key_exists('project_id', $data)) $rfq->project_id = $data['project_id'] ?? null;
        if (array_key_exists('estimated_value', $data)) $rfq->estimated_value = $data['estimated_value'] !== null ? (float) $data['estimated_value'] : null;
        if (array_key_exists('savings_percentage', $data)) $rfq->savings_percentage = $data['savings_percentage'] !== null ? (float) $data['savings_percentage'] : null;
        if (array_key_exists('submission_deadline', $data)) $rfq->submission_deadline = $data['submission_deadline'] ? Carbon::parse($data['submission_deadline']) : null;
        if (array_key_exists('closing_date', $data)) $rfq->closing_date = $data['closing_date'] ? Carbon::parse($data['closing_date']) : null;
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

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($id): void {
                $builder->where('id', $id)->orWhere('rfq_number', $id);
            })
            ->first();

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $status = (string) $request->input('status');
        $allowed = ['draft', 'published', 'closed', 'awarded', 'cancelled'];
        if ($status !== '' && in_array($status, $allowed, true)) {
            $rfq->status = $status;
            $rfq->save();
        }

        return response()->json([
            'data' => [
                'id' => $rfq->id,
                'status' => $rfq->status,
            ],
        ]);
    }

    public function duplicate(Request $request, string $id): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => 'stub-duplicate-id',
                'status' => 'draft',
            ],
        ], 201);
    }

    public function saveDraft(Request $request, string $id): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'draft',
            ],
        ]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)
        // Bulk close/archive/assign

        return response()->json([
            'data' => [
                'affected' => 0,
            ],
        ]);
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
