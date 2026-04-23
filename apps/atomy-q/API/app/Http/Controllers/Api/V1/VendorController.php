<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Api\V1\Concerns\NormalizesVendorStatus;
use App\Http\Controllers\Controller;
use App\Http\Idempotency\IdempotencyCompletion;
use App\Models\Award;
use App\Models\QuoteSubmission;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Nexus\Idempotency\Contracts\IdempotencyServiceInterface;
use Nexus\Vendor\Enums\VendorStatus;

/**
 * Vendor API controller.
 *
 * SECURITY: all queries are tenant-scoped to prevent cross-tenant data leakage.
 */
final class VendorController extends Controller
{
    use ExtractsAuthContext;
    use NormalizesVendorStatus;

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

    public function store(Request $request, IdempotencyServiceInterface $idempotency): JsonResponse
    {
        try {
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
            $vendor->tax_id = null;
            $this->applyVendorAttributes($vendor, $validated);
            $vendor->status = VendorStatus::Draft->value;
            $vendor->save();

            $response = response()->json([
                'data' => $this->serializeVendor($vendor),
            ], 201);

            return IdempotencyCompletion::succeed($request, $idempotency, $response);
        } catch (ValidationException $exception) {
            IdempotencyCompletion::fail($request, $idempotency);
            throw $exception;
        } catch (\Throwable $exception) {
            IdempotencyCompletion::fail($request, $idempotency);
            throw $exception;
        }
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
            'legal_name' => ['sometimes', 'required', 'string'],
            'display_name' => ['sometimes', 'required', 'string'],
            'registration_number' => ['sometimes', 'required', 'string'],
            'country_of_registration' => ['sometimes', 'required', 'string'],
            'primary_contact_name' => ['sometimes', 'required', 'string'],
            'primary_contact_email' => ['sometimes', 'required', 'email'],
            'primary_contact_phone' => ['sometimes', 'nullable', 'string'],
        ]);

        $this->applyVendorAttributes($vendor, $validated);
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
            $status = $this->normalizeVendorStatus((string) $vendor->status) === VendorStatus::Approved
                ? 'compliant'
                : 'review_required';
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
        $status = $this->normalizeVendorStatus((string) $vendor->status);
        $approvalRecord = $this->serializeApprovalRecord($vendor);

        return [
            'id' => (string) $vendor->id,
            'legal_name' => (string) $vendor->legal_name,
            'display_name' => (string) $vendor->display_name,
            'registration_number' => (string) $vendor->registration_number,
            'country_of_registration' => (string) $vendor->country_of_registration,
            'primary_contact_name' => (string) $vendor->primary_contact_name,
            'primary_contact_email' => (string) $vendor->primary_contact_email,
            'primary_contact_phone' => $this->nullableString($vendor->primary_contact_phone),
            'status' => $status->value,
            'approval_record' => $approvalRecord,
            'created_at' => $vendor->created_at?->toAtomString(),
            'updated_at' => $vendor->updated_at?->toAtomString(),
            'name' => (string) $vendor->legal_name,
            'trading_name' => (string) $vendor->display_name,
            'country_code' => (string) $vendor->country_of_registration,
            'email' => (string) $vendor->primary_contact_email,
            'phone' => $this->nullableString($vendor->primary_contact_phone),
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

    /**
     * @param array<string, mixed> $validated
     */
    private function applyVendorAttributes(Vendor $vendor, array $validated): void
    {
        if (array_key_exists('legal_name', $validated)) {
            $legalName = (string) $validated['legal_name'];
            $vendor->name = $legalName;
            $vendor->legal_name = $legalName;
        }

        if (array_key_exists('display_name', $validated)) {
            $displayName = (string) $validated['display_name'];
            $vendor->trading_name = $displayName;
            $vendor->display_name = $displayName;
        }

        if (array_key_exists('registration_number', $validated)) {
            $vendor->registration_number = (string) $validated['registration_number'];
        }

        if (array_key_exists('country_of_registration', $validated)) {
            $countryOfRegistration = (string) $validated['country_of_registration'];
            $vendor->country_code = $countryOfRegistration;
            $vendor->country_of_registration = $countryOfRegistration;
        }

        if (array_key_exists('primary_contact_name', $validated)) {
            $vendor->primary_contact_name = (string) $validated['primary_contact_name'];
        }

        if (array_key_exists('primary_contact_email', $validated)) {
            $primaryContactEmail = (string) $validated['primary_contact_email'];
            $vendor->email = $primaryContactEmail;
            $vendor->primary_contact_email = $primaryContactEmail;
        }

        if (array_key_exists('primary_contact_phone', $validated)) {
            $phone = $validated['primary_contact_phone'];
            // Preserve nulls: if incoming is null, persist null; else string
            $normalizedPhone = $phone !== null ? (string) $phone : null;

            $vendor->phone = $normalizedPhone;
            $vendor->primary_contact_phone = $normalizedPhone;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
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
