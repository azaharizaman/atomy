<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\Award;
use App\Models\QuoteSubmission;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vendor API controller.
 *
 * SECURITY: When implementing real data, all queries MUST be scoped by
 * $this->tenantId($request) to prevent cross-tenant data leakage.
 * Do not use tenant_id from request body or query.
 */
final class VendorController extends Controller
{
    use ExtractsAuthContext;

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $pagination = $this->paginationParams($request);

        $query = Vendor::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name');

        $status = $request->query('status');
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $search = $request->query('search', $request->query('q'));
        if (is_string($search) && trim($search) !== '') {
            $needle = '%' . trim($search) . '%';
            $query->where(static function ($builder) use ($needle): void {
                $builder->where('name', 'like', $needle)
                    ->orWhere('trading_name', 'like', $needle)
                    ->orWhere('registration_number', 'like', $needle)
                    ->orWhere('email', 'like', $needle);
            });
        }

        $paginator = $query->paginate($pagination['per_page'], ['*'], 'page', $pagination['page']);

        return response()->json([
            'data' => $paginator->getCollection()
                ->map(fn (Vendor $vendor): array => $this->serializeVendor($vendor))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $vendor = $this->findVendor($this->tenantId($request), $id);
        if ($vendor === null) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        return response()->json([
            'data' => $this->serializeVendor($vendor),
        ]);
    }

    public function performance(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $vendor = $this->findVendor($tenantId, $id);
        if ($vendor === null) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $quoteMetrics = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('vendor_id', $vendor->id)
            ->selectRaw('COUNT(*) AS quotes_submitted')
            ->selectRaw("SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) AS quotes_ready")
            ->selectRaw('AVG(confidence) AS average_confidence')
            ->first();

        $quotesSubmitted = (int) ($quoteMetrics?->quotes_submitted ?? 0);
        $quotesReady = (int) ($quoteMetrics?->quotes_ready ?? 0);
        $awardsWon = Award::query()
            ->where('tenant_id', $tenantId)
            ->where('vendor_id', $vendor->id)
            ->count();
        $averageConfidence = $quoteMetrics?->average_confidence;

        $score = $this->performanceScore($quotesSubmitted, $quotesReady, $awardsWon);

        return response()->json([
            'data' => [
                'vendor_id' => (string) $vendor->id,
                'score' => $score,
                'metrics' => [
                    'quotes_submitted' => $quotesSubmitted,
                    'quotes_ready' => $quotesReady,
                    'awards_won' => $awardsWon,
                    'average_confidence' => $averageConfidence !== null ? round((float) $averageConfidence, 2) : null,
                ],
            ],
        ]);
    }

    public function compliance(Request $request, string $id): JsonResponse
    {
        $vendor = $this->findVendor($this->tenantId($request), $id);
        if ($vendor === null) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $metadata = is_array($vendor->metadata) ? $vendor->metadata : [];
        $status = $metadata['compliance_status'] ?? null;
        if (! is_string($status) || $status === '') {
            $status = $vendor->status === 'active' ? 'compliant' : 'review_required';
        }

        return response()->json([
            'data' => [
                'vendor_id' => (string) $vendor->id,
                'status' => $status,
                'kyc_verified' => (bool) ($metadata['kyc_verified'] ?? false),
                'sanctions_screened' => (bool) ($metadata['sanctions_screened'] ?? false),
                'last_checked_at' => isset($metadata['compliance_last_checked_at']) && is_string($metadata['compliance_last_checked_at'])
                    ? $metadata['compliance_last_checked_at']
                    : null,
            ],
        ]);
    }

    public function history(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $vendor = $this->findVendor($tenantId, $id);
        if ($vendor === null) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $awards = Award::query()
            ->where('tenant_id', $tenantId)
            ->where('vendor_id', $vendor->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'rfq_id', 'status', 'amount', 'currency', 'signoff_at', 'created_at']);

        return response()->json([
            'data' => $awards->map(static fn (Award $award): array => [
                'award_id' => (string) $award->id,
                'rfq_id' => (string) $award->rfq_id,
                'status' => (string) $award->status,
                'amount' => $award->amount !== null ? (float) $award->amount : null,
                'currency' => (string) $award->currency,
                'signed_off_at' => $award->signoff_at?->toAtomString(),
                'created_at' => $award->created_at?->toAtomString(),
            ])->values()->all(),
        ]);
    }

    private function findVendor(string $tenantId, string $vendorId): ?Vendor
    {
        return Vendor::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $vendorId)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeVendor(Vendor $vendor): array
    {
        return [
            'id' => (string) $vendor->id,
            'name' => (string) $vendor->name,
            'trading_name' => $vendor->trading_name,
            'registration_number' => $vendor->registration_number,
            'tax_id' => $vendor->tax_id,
            'country_code' => (string) $vendor->country_code,
            'email' => (string) $vendor->email,
            'phone' => $vendor->phone,
            'status' => (string) $vendor->status,
            'onboarded_at' => $vendor->onboarded_at?->toAtomString(),
            'created_at' => $vendor->created_at?->toAtomString(),
            'updated_at' => $vendor->updated_at?->toAtomString(),
        ];
    }

    private function performanceScore(int $quotesSubmitted, int $quotesReady, int $awardsWon): float
    {
        if ($quotesSubmitted <= 0) {
            return 0.0;
        }

        $readyRatio = $quotesReady / $quotesSubmitted;
        $score = ($readyRatio * 70.0) + (min($awardsWon, 5) * 6.0);

        return round(min(100.0, max(0.0, $score)), 2);
    }
}
