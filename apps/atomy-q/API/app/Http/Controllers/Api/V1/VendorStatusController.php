<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Api\V1\Concerns\NormalizesVendorStatus;
use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Nexus\Vendor\Contracts\VendorStatusTransitionPolicyInterface;
use Nexus\Vendor\Enums\VendorStatus;
use Nexus\Vendor\Exceptions\InvalidVendorStatusTransition;

final class VendorStatusController extends Controller
{
    use ExtractsAuthContext;
    use NormalizesVendorStatus;

    public function __construct(
        private readonly VendorStatusTransitionPolicyInterface $statusTransitionPolicy,
    ) {
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $vendor = $this->findVendor($tenantId, $id);

        if ($vendor === null) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::enum(VendorStatus::class)],
            'approval_note' => ['required_if:status,approved', 'string'],
        ]);

        $currentStatus = $this->normalizeVendorStatus((string) $vendor->status);
        $targetStatus = VendorStatus::from((string) $validated['status']);

        try {
            $this->statusTransitionPolicy->assertCanTransition($currentStatus, $targetStatus);
        } catch (InvalidVendorStatusTransition $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $vendor->status = $targetStatus->value;

        if ($targetStatus === VendorStatus::Approved) {
            $vendor->approved_by_user_id = $this->userId($request);
            $vendor->approved_at = now();
            $vendor->approval_note = (string) $validated['approval_note'];
        }

        $vendor->save();

        return response()->json([
            'data' => $this->serializeVendor($vendor),
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
        return [
            'id' => (string) $vendor->id,
            'legal_name' => (string) $vendor->legal_name,
            'display_name' => (string) $vendor->display_name,
            'registration_number' => (string) $vendor->registration_number,
            'country_of_registration' => (string) $vendor->country_of_registration,
            'primary_contact_name' => (string) $vendor->primary_contact_name,
            'primary_contact_email' => (string) $vendor->primary_contact_email,
            'primary_contact_phone' => $this->nullableString($vendor->primary_contact_phone),
            'status' => $this->normalizeVendorStatus((string) $vendor->status)->value,
            'approval_record' => $this->serializeApprovalRecord($vendor),
            'created_at' => $vendor->created_at?->toAtomString(),
            'updated_at' => $vendor->updated_at?->toAtomString(),
            'name' => (string) $vendor->legal_name,
            'trading_name' => (string) $vendor->display_name,
            'country_code' => (string) $vendor->country_of_registration,
            'email' => (string) $vendor->primary_contact_email,
            'phone' => $this->nullableString($vendor->phone),
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

    private function normalizeIdentifier(string $value): string
    {
        return strtolower(trim($value));
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
