<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class QuoteSubmissionController extends Controller
{
    use ExtractsAuthContext;

    /**
     * GET /quote-submissions
     * Params: status, rfq_id, vendor_id, page, per_page
     * Scoped by tenant_id.
     */
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
                'from' => null,
                'to' => null,
            ],
        ]);
    }

    /**
     * POST /quote-submissions/upload
     * Scoped by tenant_id. Accepts multipart file or JSON with rfq_id, vendor_id, vendor_name (for seed/testing).
     */
    public function upload(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $validator = Validator::make($request->all(), [
            'rfq_id' => ['required', 'string'],
            'vendor_id' => ['nullable', 'string'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rfqId = $request->input('rfq_id');
        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($rfqId): void {
                $builder->where('id', $rfqId)->orWhere('rfq_number', $rfqId);
            })
            ->first();

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $vendorId = $request->input('vendor_id');
        $vendorName = $request->input('vendor_name', 'Vendor');
        $filePath = null;
        $fileType = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('quote-submissions', 'local');
            $fileType = $file->getMimeType();
        }

        $qs = new QuoteSubmission();
        $qs->tenant_id = $tenantId;
        $qs->rfq_id = $rfq->id;
        $qs->vendor_id = $vendorId;
        $qs->vendor_name = $vendorName;
        $qs->status = 'processing';
        $qs->file_path = $filePath;
        $qs->file_type = $fileType;
        $qs->submitted_at = now();
        $qs->confidence = 85.0;
        $qs->line_items_count = 0;
        $qs->warnings_count = 0;
        $qs->errors_count = 0;
        $qs->save();

        return response()->json([
            'data' => [
                'id' => $qs->id,
                'rfq_id' => $qs->rfq_id,
                'vendor_id' => $qs->vendor_id,
                'vendor_name' => $qs->vendor_name,
                'status' => $qs->status,
            ],
        ], 201);
    }

    /**
     * GET /quote-submissions/:id
     * Two-tab detail data.
     * Scoped by tenant_id.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'rfq_id' => null,
                'vendor_id' => null,
                'status' => 'pending',
                'tab_overview' => [],
                'tab_details' => [],
            ],
        ]);
    }

    /**
     * PATCH /quote-submissions/:id/status
     * Accept/reject.
     * Scoped by tenant_id.
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $qs = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if ($qs === null) {
            return response()->json(['message' => 'Quote submission not found'], 404);
        }

        $status = (string) $request->input('status');
        $allowed = ['processing', 'parsed', 'accepted', 'rejected'];
        if ($status !== '' && in_array($status, $allowed, true)) {
            $qs->status = $status;
            $qs->save();
        }

        return response()->json([
            'data' => [
                'id' => $qs->id,
                'status' => $qs->status,
            ],
        ]);
    }

    /**
     * POST /quote-submissions/:id/replace
     * Scoped by tenant_id.
     */
    public function replace(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'replaced',
            ],
        ]);
    }

    /**
     * POST /quote-submissions/:id/reparse
     * Scoped by tenant_id.
     */
    public function reparse(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'reparsing',
            ],
        ], 202);
    }

    /**
     * POST /quote-submissions/:id/assign
     * Scoped by tenant_id.
     */
    public function assign(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'assigned_to' => $this->userId($request),
            ],
        ]);
    }
}
