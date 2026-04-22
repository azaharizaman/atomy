<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\RequisitionSelectedVendor;
use App\Models\Rfq;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Nexus\Vendor\Enums\VendorStatus;

final class RequisitionVendorSelectionController extends Controller
{
    use ExtractsAuthContext;

    public function index(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $rfq = $this->findRfq($tenantId, $rfqId);

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $selections = RequisitionSelectedVendor::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfq->id)
            ->with(['vendor'])
            ->orderBy('selected_at')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $selections->map(fn (RequisitionSelectedVendor $selection): array => $this->serializeSelection($selection))->values()->all(),
        ]);
    }

    public function update(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $rfq = $this->findRfq($tenantId, $rfqId);

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $validated = Validator::make($request->all(), [
            'vendor_ids' => ['required', 'array', 'min:1', 'max:100'],
            'vendor_ids.*' => ['required', 'string', 'distinct'],
        ])->validate();

        /** @var array<int, string> $vendorIds */
        $vendorIds = array_values(array_map('strval', $validated['vendor_ids']));

        $vendors = Vendor::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $vendorIds)
            ->get()
            ->keyBy(fn (Vendor $vendor): string => (string) $vendor->id);

        if ($vendors->count() !== count($vendorIds)) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $invalidVendor = $this->firstNonApprovedVendor($vendors, $vendorIds);
        if ($invalidVendor !== null) {
            return response()->json([
                'message' => 'Selected vendors must be approved.',
                'errors' => [
                    'vendor_ids' => ['Selected vendors must all be approved.'],
                ],
            ], 422);
        }

        $rows = DB::transaction(function () use ($rfq, $tenantId, $vendorIds, $request, $vendors): Collection {
            RequisitionSelectedVendor::query()
                ->where('tenant_id', $tenantId)
                ->where('rfq_id', $rfq->id)
                ->delete();

            $now = now('UTC');
            $userId = $this->userId($request);
            $payload = array_map(
                static fn (string $vendorId): array => [
                    'id' => (string) Str::ulid(),
                    'tenant_id' => $tenantId,
                    'rfq_id' => $rfq->id,
                    'vendor_id' => $vendorId,
                    'selected_by_user_id' => $userId,
                    'selected_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $vendorIds,
            );

            RequisitionSelectedVendor::query()->insert($payload);

            $selections = new Collection();
            foreach (RequisitionSelectedVendor::hydrate($payload) as $selection) {
                $selection->setRelation('vendor', $vendors->get((string) $selection->vendor_id));
                $selections->push($selection);
            }

            return $selections;
        });

        return response()->json([
            'data' => $rows->map(fn (RequisitionSelectedVendor $selection): array => $this->serializeSelection($selection))->values()->all(),
        ]);
    }

    private function findRfq(string $tenantId, string $rfqId): ?Rfq
    {
        return Rfq::query()
            ->whereRaw('lower(tenant_id) = ?', [$this->normalizeIdentifier($tenantId)])
            ->where(function ($builder) use ($rfqId): void {
                $builder
                    ->whereRaw('lower(id) = ?', [$this->normalizeIdentifier($rfqId)])
                    ->orWhereRaw('lower(rfq_number) = ?', [$this->normalizeIdentifier($rfqId)]);
            })
            ->first();
    }

    /**
     * @param Collection<string, Vendor> $vendors
     */
    private function firstNonApprovedVendor(Collection $vendors, array $vendorIds): ?Vendor
    {
        foreach ($vendorIds as $vendorId) {
            $vendor = $vendors->get($vendorId);

            if ($vendor === null) {
                continue;
            }

            if ($this->normalizeVendorStatus((string) $vendor->status) !== VendorStatus::Approved) {
                return $vendor;
            }
        }

        return null;
    }

    private function serializeSelection(RequisitionSelectedVendor $selection): array
    {
        $vendor = $selection->vendor;

        return [
            'id' => (string) $selection->id,
            'rfq_id' => (string) $selection->rfq_id,
            'vendor_id' => (string) $selection->vendor_id,
            'vendor_name' => $this->vendorDisplayName($vendor),
            'vendor_display_name' => $this->vendorDisplayName($vendor),
            'vendor_email' => $this->vendorEmail($vendor),
            'status' => $vendor !== null ? (string) $vendor->status : null,
            'selected_at' => $selection->selected_at?->toAtomString(),
            'selected_by_user_id' => $selection->selected_by_user_id !== null ? (string) $selection->selected_by_user_id : null,
        ];
    }

    private function vendorDisplayName(?Vendor $vendor): ?string
    {
        if ($vendor === null) {
            return null;
        }

        foreach ([$vendor->display_name, $vendor->legal_name, $vendor->name] as $candidate) {
            $value = trim((string) $candidate);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function vendorEmail(?Vendor $vendor): ?string
    {
        if ($vendor === null) {
            return null;
        }

        foreach ([$vendor->primary_contact_email, $vendor->email] as $candidate) {
            $value = trim((string) $candidate);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeIdentifier(string $value): string
    {
        return strtolower(trim($value));
    }

    private function normalizeVendorStatus(string $status): ?VendorStatus
    {
        $normalized = trim(strtolower($status));

        return VendorStatus::tryFrom($normalized);
    }
}
