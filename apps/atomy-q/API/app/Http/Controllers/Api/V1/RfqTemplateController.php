<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Idempotency\IdempotencyCompletion;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Idempotency\Contracts\IdempotencyServiceInterface;

final class RfqTemplateController extends Controller
{
    use ExtractsAuthContext;

    /** GET /rfq-templates */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $pagination = $this->paginationParams($request);

        return response()->json([
            'data' => [],
            'meta' => [
                'current_page' => $pagination['page'],
                'per_page' => $pagination['per_page'],
                'total' => 0,
            ],
        ]);
    }

    /** POST /rfq-templates */
    public function store(Request $request, IdempotencyServiceInterface $idempotency): JsonResponse
    {
        try {
            $tenantId = $this->tenantId($request);

            $response = response()->json([
                'data' => [
                    'id' => 'stub-template-id',
                    'tenant_id' => $tenantId,
                    'name' => $request->input('name', ''),
                    'status' => 'draft',
                ],
            ], 201);

            return IdempotencyCompletion::succeed($request, $idempotency, $response);
        } catch (\Throwable $e) {
            IdempotencyCompletion::fail($request, $idempotency);
            throw $e;
        }
    }

    /** GET /rfq-templates/{id} */
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [
                'id' => $id,
                'tenant_id' => $tenantId,
                'name' => '',
                'description' => '',
                'category' => '',
                'status' => 'draft',
                'line_items_template' => [],
                'terms_template' => [],
            ],
        ]);
    }

    /** PUT /rfq-templates/{id} */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => ['id' => $id, 'updated' => true],
        ]);
    }

    /** PATCH /rfq-templates/{id}/status */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [
                'id' => $id,
                'status' => $request->input('status', 'published'),
            ],
        ]);
    }

    /** POST /rfq-templates/{id}/duplicate */
    public function duplicate(Request $request, string $id, IdempotencyServiceInterface $idempotency): JsonResponse
    {
        try {
            $this->tenantId($request);

            $response = response()->json([
                'data' => [
                    'id' => 'stub-duplicated-template-id',
                    'source_id' => $id,
                    'status' => 'draft',
                ],
            ], 201);

            return IdempotencyCompletion::succeed($request, $idempotency, $response);
        } catch (\Throwable $e) {
            IdempotencyCompletion::fail($request, $idempotency);
            throw $e;
        }
    }

    /** POST /rfq-templates/{id}/apply */
    public function apply(Request $request, string $id, IdempotencyServiceInterface $idempotency): JsonResponse
    {
        try {
            $this->tenantId($request);

            $response = response()->json([
                'data' => [
                    'template_id' => $id,
                    'applied' => true,
                    'pre_filled' => [],
                ],
            ]);

            return IdempotencyCompletion::succeed($request, $idempotency, $response);
        } catch (\Throwable $e) {
            IdempotencyCompletion::fail($request, $idempotency);
            throw $e;
        }
    }
}
