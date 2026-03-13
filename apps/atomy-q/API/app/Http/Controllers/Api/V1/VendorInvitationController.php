<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\Rfq;
use App\Models\VendorInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class VendorInvitationController extends Controller
{
    use ExtractsAuthContext;

    public function index(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($rfqId): void {
                $builder->where('id', $rfqId)->orWhere('rfq_number', $rfqId);
            })
            ->first();

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $invitations = VendorInvitation::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfq->id)
            ->orderBy('created_at')
            ->get();

        $rows = $invitations->map(static function (VendorInvitation $inv): array {
            return [
                'id' => $inv->id,
                'rfq_id' => $inv->rfq_id,
                'vendor_id' => $inv->vendor_id,
                'vendor_email' => $inv->vendor_email,
                'vendor_name' => $inv->vendor_name,
                'status' => $inv->status,
                'invited_at' => $inv->invited_at?->toAtomString(),
                'responded_at' => $inv->responded_at?->toAtomString(),
            ];
        })->values()->all();

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($rfqId): void {
                $builder->where('id', $rfqId)->orWhere('rfq_number', $rfqId);
            })
            ->first();

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'vendor_email' => ['required', 'string', 'email'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'vendor_id' => ['nullable', 'string', 'max:26'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $inv = new VendorInvitation();
        $inv->tenant_id = $tenantId;
        $inv->rfq_id = $rfq->id;
        $inv->vendor_id = $request->input('vendor_id');
        $inv->vendor_email = $request->input('vendor_email');
        $inv->vendor_name = $request->input('vendor_name');
        $inv->status = 'pending';
        $inv->invited_at = now();
        $inv->channel = $request->input('channel', 'email');
        $inv->save();

        return response()->json([
            'data' => [
                'id' => $inv->id,
                'rfq_id' => $inv->rfq_id,
                'vendor_email' => $inv->vendor_email,
                'vendor_name' => $inv->vendor_name,
                'status' => $inv->status,
                'invited_at' => $inv->invited_at?->toAtomString(),
            ],
        ], 201);
    }

    public function remind(Request $request, string $rfqId, string $invId): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => $invId,
                'rfq_id' => $rfqId,
                'status' => 'pending',
            ],
        ]);
    }
}
