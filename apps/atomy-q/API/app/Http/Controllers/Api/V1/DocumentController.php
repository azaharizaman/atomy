<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Document API controller (Section 20).
 *
 * Handles documents, downloads, previews, and evidence bundles.
 */
final class DocumentController extends Controller
{
    use ExtractsAuthContext;

    /**
     * List documents.
     *
     * GET /documents
     * Query: type, rfq_id, vendor_id, tags, page, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $params = $this->paginationParams($request);

        return response()->json([
            'data' => [],
            'meta' => [
                'current_page' => $params['page'],
                'per_page' => $params['per_page'],
                'total' => 0,
            ],
        ]);
    }

    /**
     * Show a single document.
     *
     * GET /documents/:id
     */
    public function show(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'name' => 'Stub document',
                'type' => 'pdf',
                'created_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get document download URL or redirect.
     *
     * GET /documents/:id/download
     */
    public function download(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'download_url' => 'https://example.com/documents/' . $id . '/download',
                'expires_at' => now()->addMinutes(30)->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get document preview URL.
     *
     * GET /documents/:id/preview
     */
    public function preview(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'preview_url' => 'https://example.com/documents/' . $id . '/preview',
            ],
        ]);
    }

    /**
     * List evidence bundles.
     *
     * GET /evidence-bundles
     */
    public function bundles(Request $request): JsonResponse
    {
        $params = $this->paginationParams($request);

        return response()->json([
            'data' => [],
            'meta' => [
                'current_page' => $params['page'],
                'per_page' => $params['per_page'],
                'total' => 0,
            ],
        ]);
    }

    /**
     * Create an evidence bundle.
     *
     * POST /evidence-bundles
     */
    public function createBundle(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => 'stub-bundle-id',
                'name' => 'Stub bundle',
                'status' => 'draft',
            ],
        ], 201);
    }

    /**
     * Show a single evidence bundle.
     *
     * GET /evidence-bundles/:id
     */
    public function showBundle(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'name' => 'Stub bundle',
                'documents' => [],
            ],
        ]);
    }

    /**
     * Add document to evidence bundle.
     *
     * POST /evidence-bundles/:id/add-document
     */
    public function addDocumentToBundle(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'bundle_id' => $id,
                'document_id' => 'stub-document-id',
            ],
        ]);
    }

    /**
     * Finalize evidence bundle.
     *
     * POST /evidence-bundles/:id/finalize
     */
    public function finalizeBundle(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'finalized',
            ],
        ]);
    }

    /**
     * Export evidence bundle.
     *
     * GET /evidence-bundles/:id/export
     */
    public function exportBundle(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'download_url' => 'https://example.com/evidence-bundles/' . $id . '/export',
                'expires_at' => now()->addHours(24)->toIso8601String(),
            ],
        ]);
    }
}
