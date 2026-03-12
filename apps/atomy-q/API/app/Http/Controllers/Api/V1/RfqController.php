<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\Rfq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RfqController extends Controller
{
    use ExtractsAuthContext;

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
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => 'stub-id',
                'status' => 'draft',
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
            ],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'draft',
            ],
        ]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'submitted',
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
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [],
        ]);
    }

    public function storeLineItem(Request $request, string $rfqId): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => 'stub-line-item-id',
                'rfq_id' => $rfqId,
            ],
        ], 201);
    }

    public function updateLineItem(Request $request, string $rfqId, string $itemId): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => $itemId,
                'rfq_id' => $rfqId,
            ],
        ]);
    }

    public function destroyLineItem(Request $request, string $rfqId, string $itemId): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([], 204);
    }
}
