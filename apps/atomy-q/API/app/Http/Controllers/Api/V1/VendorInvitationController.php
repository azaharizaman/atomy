<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Idempotency\IdempotencyCompletion;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\Rfq;
use App\Models\VendorInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Nexus\Idempotency\Contracts\IdempotencyServiceInterface;

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

    public function store(Request $request, string $rfqId, IdempotencyServiceInterface $idempotency): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($rfqId): void {
                $builder->where('id', $rfqId)->orWhere('rfq_number', $rfqId);
            })
            ->first();

        if ($rfq === null) {
            IdempotencyCompletion::fail($request, $idempotency);

            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'vendor_email' => ['required', 'string', 'email'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'vendor_id' => ['nullable', 'string', 'max:26'],
        ]);

        if ($validator->fails()) {
            IdempotencyCompletion::fail($request, $idempotency);

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

        $response = response()->json([
            'data' => [
                'id' => $inv->id,
                'rfq_id' => $inv->rfq_id,
                'vendor_email' => $inv->vendor_email,
                'vendor_name' => $inv->vendor_name,
                'status' => $inv->status,
                'invited_at' => $inv->invited_at?->toAtomString(),
            ],
        ], 201);

        return IdempotencyCompletion::succeed($request, $idempotency, $response);
    }

    public function remind(Request $request, string $rfqId, string $invId, IdempotencyServiceInterface $idempotency): JsonResponse
    {
        try {
            // TODO: tenant scoping via $this->tenantId($request)

            $response = response()->json([
                'data' => [
                    'id' => $invId,
                    'rfq_id' => $rfqId,
                    'status' => 'pending',
                ],
            ]);

            return IdempotencyCompletion::succeed($request, $idempotency, $response);
        } catch (\Throwable $e) {
            IdempotencyCompletion::fail($request, $idempotency);
            throw $e;
        }
    }
}
