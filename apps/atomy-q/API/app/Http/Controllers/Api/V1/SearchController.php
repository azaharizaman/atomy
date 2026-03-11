<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Search API controller (Section 26).
 *
 * Handles global search across RFQs, vendors, documents.
 */
final class SearchController extends Controller
{
    use ExtractsAuthContext;

    /**
     * Global search.
     *
     * GET /search?q=:query
     */
    public function search(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');

        return response()->json([
            'data' => [],
            'meta' => [
                'query' => $query,
                'total' => 0,
            ],
        ]);
    }
}
