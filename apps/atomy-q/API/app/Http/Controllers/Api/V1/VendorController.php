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
use Nexus\Vendor\Enums\VendorStatus;

/**
 * Vendor API controller.
 *
 * SECURITY: all queries are tenant-scoped to prevent cross-tenant data leakage.
 */
final class VendorController extends Controller
{
    use ExtractsAuthContext;

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $normalizedTenantId = $this->normalizeIdentifier($tenantId);
        $pagination = $this->paginationParams($request);

        $query = Vendor::query()
            ->whereRaw('lower(tenant_id) = ?', [$normalizedTenantId])
            ->orderBy('display_name')
            ->orderBy('id');

        if (is_string($status = $request->query('status')) && trim($status) !== '') {
            $query->where('status', trim($status));
        }

        $search = $request->query('search', $request->query('q'));
        if (is_string($search) && trim($search) !== '') {
            $needle = '%' . trim($search) . '%';
            $query->where(static function ($builder) use ($needle): void {
                $builder
                    ->where('legal_name', 'like', $needle)
                    ->orWhere('display_name', 'like', $needle)
                    ->orWhere('registration_number', 'like', $needle)
                    ->orWhere('primary_contact_email', 'like', $needle);
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

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $validated = $request->validate([
            'legal_name' => ['required', 'string'],
            'display_name' => ['required', 'string'],
            'registration_number' => ['required', 'string'],
            'country_of_registration' => ['required', 'string'],
            'primary_contact_name' => ['required', 'string'],
            'primary_contact_email' => ['required', 'email'],
            'primary_contact_phone' => ['nullable', 'string'],
        ]);

        $vendor = new Vendor();
        $vendor->tenant_id = $this->normalizeIdentifier($tenantId);
        $vendor->name = (string) $validated['legal_name'];
        $vendor->trading_name = (string) $validated['display_name'];
        $vendor->registration_number = (string) $validated['registration_number'];
        $vendor->tax_id = null;
        $vendor->country_code = (string) $validated['country_of_registration'];
        $vendor->email = (string) $validated['primary_contact_email'];
        $vendor->phone = array_key_exists('primary_contact_phone', $validated)
            ? $validated['primary_contact_phone'] !== null
                ? (string) $validated['primary_contact_phone']
                : null
            : null;
        $vendor->legal_name = (string) $validated['legal_name'];
        $vendor->display_name = (string) $validated['display_name'];
        $vendor->country_of_registration = (string) $validated['country_of_registration'];
        $vendor->primary_contact_name = (string) $validated['primary_contact_name'];
        $vendor->primary_contact_email = (string) $validated['primary_contact_email'];
        $vendor->primary_contact_phone = array_key_exists('primary_contact_phone', $validated)
            ? $validated['primary_contact_phone'] !== null
                ? (string) $validated['primary_contact_phone']
                : null
            : null;
        $vendor->status = VendorStatus::Draft->value;
        $vendor->save();

        return response()->json([
            'data' => $this->serializeVendor($vendor),
        ], 201);
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

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $vendor = $this->findVendor($tenantId, $id);

        if ($vendor === null) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $validated = $request->validate([
            'legal_name' => ['required', 'string'],
            'display_name' => ['required', 'string'],
            'registration_number' => ['required', 'string'],
            'country_of_registration' => ['required', 'string'],
            'primary_contact_name' => ['required', 'string'],
            'primary_contact_email' => ['required', 'email'],
            'primary_contact_phone' => ['nullable', 'string'],
        ]);

        $vendor->legal_name = (string) $validated['legal_name'];
        $vendor->display_name = (string) $validated['display_name'];
        $vendor->registration_number = (string) $validated['registration_number'];
        $vendor->name = (string) $validated['legal_name'];
        $vendor->trading_name = (string) $validated['display_name'];
        $vendor->country_code = (string) $validated['country_of_registration'];
        $vendor->email = (string) $validated['primary_contact_email'];
        $vendor->phone = array_key_exists('primary_contact_phone', $validated)
            ? $validated['primary_contact_phone'] !== null
                ? (string) $validated['primary_contact_phone']
                : null
            : null;
        $vendor->country_of_registration = (string) $validated['country_of_registration'];
        $vendor->primary_contact_name = (string) $validated['primary_contact_name'];
        $vendor->primary_contact_email = (string) $validated['primary_contact_email'];
        $vendor->primary_contact_phone = array_key_exists('primary_contact_phone', $validated)
            ? $validated['primary_contact_phone'] !== null
                ? (string) $validated['primary_contact_phone']
                : null
            : null;
        $vendor->save();

        return response()->json([
            'data' => $this->serializeVendor($vendor),
        ]);
    }

    public function performance(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $normalizedTenantId = $this->normalizeIdentifier($tenantId);
        $vendor = $this->findVendor($tenantId, $id);
        if ($vendor === null) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $quoteMetrics = QuoteSubmission::query()
            ->whereRaw('lower(tenant_id) = ?', [$normalizedTenantId])
            ->where('vendor_id', $vendor->id)
            ->selectRaw('COUNT(*) AS quotes_submitted')
            ->selectRaw("SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) AS quotes_ready")
            ->selectRaw('AVG(confidence) AS average_confidence')
            ->first();

        $quotesSubmitted = (int) ($quoteMetrics?->quotes_submitted ?? 0);
        $quotesReady = (int) ($quoteMetrics?->quotes_ready ?? 0);
        $awardsWon = Award::query()
            ->whereRaw('lower(tenant_id) = ?', [$normalizedTenantId])
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
        $normalizedTenantId = $this->normalizeIdentifier($tenantId);
        $vendor = $this->findVendor($tenantId, $id);
        if ($vendor === null) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $awards = Award::query()
            ->whereRaw('lower(tenant_id) = ?', [$normalizedTenantId])
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
            ->whereRaw('lower(tenant_id) = ?', [$this->normalizeIdentifier($tenantId)])
            ->whereRaw('lower(id) = ?', [$this->normalizeIdentifier($vendorId)])
            ->first();
    }

    private function serializeVendor(Vendor $vendor): array
    {
        $status = $this->normalizeStatus((string) $vendor->status);
        $approvalRecord = $this->serializeApprovalRecord($vendor);

        return [
            'id' => (string) $vendor->id,
            'legal_name' => (string) $vendor->legal_name,
            'display_name' => (string) $vendor->display_name,
            'registration_number' => (string) $vendor->registration_number,
            'country_of_registration' => (string) $vendor->country_of_registration,
            'primary_contact_name' => (string) $vendor->primary_contact_name,
            'primary_contact_email' => (string) $vendor->primary_contact_email,
            'primary_contact_phone' => $vendor->primary_contact_phone,
            'status' => $status->value,
            'approval_record' => $approvalRecord,
            'created_at' => $vendor->created_at?->toAtomString(),
            'updated_at' => $vendor->updated_at?->toAtomString(),
            'name' => (string) $vendor->legal_name,
            'trading_name' => (string) $vendor->display_name,
            'country_code' => (string) $vendor->country_of_registration,
            'email' => (string) $vendor->primary_contact_email,
            'phone' => $vendor->primary_contact_phone,
        ];
    }

    private function serializeApprovalRecord(Vendor $vendor): ?array
    {
        if ($vendor->approved_by_user_id === null && $vendor->approved_at === null && $vendor->approval_note === null) {
            return null;
        }

        return [
            'approved_by_user_id' => $vendor->approved_by_user_id,
            'approved_at' => $vendor->approved_at?->toAtomString(),
            'approval_note' => $vendor->approval_note,
        ];
    }

    private function normalizeStatus(string $status): VendorStatus
    {
        return match (trim(strtolower($status))) {
            'active' => VendorStatus::Approved,
            'inactive' => VendorStatus::Suspended,
            'draft' => VendorStatus::Draft,
            'under_review' => VendorStatus::UnderReview,
            'approved' => VendorStatus::Approved,
            'restricted' => VendorStatus::Restricted,
            'suspended' => VendorStatus::Suspended,
            'archived' => VendorStatus::Archived,
            default => VendorStatus::Draft,
        };
    }

    private function normalizeIdentifier(string $value): string
    {
        return strtolower(trim($value));
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
