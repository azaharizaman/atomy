<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Idempotency\IdempotencyCompletion;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\RequisitionSelectedVendor;
use App\Models\Rfq;
use App\Models\Vendor;
use App\Models\VendorInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Nexus\Idempotency\Contracts\IdempotencyServiceInterface;
use Nexus\Sourcing\Exceptions\RfqLifecyclePreconditionException;
use Nexus\SourcingOperations\Contracts\RfqLifecycleCoordinatorInterface;
use Nexus\SourcingOperations\DTOs\RemindRfqInvitationCommand;
use Nexus\Vendor\Enums\VendorStatus;

final class VendorInvitationController extends Controller
{
    use ExtractsAuthContext;

    public function __construct(
        private readonly RfqLifecycleCoordinatorInterface $rfqLifecycle,
    ) {
    }

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
                'reminded_at' => $inv->reminded_at?->toAtomString(),
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
            'vendor_id' => ['required', 'string', 'max:26'],
            'channel' => ['nullable', 'string', 'in:email'],
        ]);

        if ($validator->fails()) {
            IdempotencyCompletion::fail($request, $idempotency);

            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vendorId = (string) $validator->validated()['vendor_id'];
        $vendor = $this->findVendor($tenantId, $vendorId);

        if ($vendor === null) {
            IdempotencyCompletion::fail($request, $idempotency);

            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $vendorStatus = $this->normalizeVendorStatus((string) $vendor->status);
        if ($vendorStatus === null) {
            IdempotencyCompletion::fail($request, $idempotency);

            return response()->json([
                'message' => 'Vendor has an unrecognized status.',
                'errors' => [
                    'vendor_id' => ['Vendor has an unrecognized status.'],
                ],
            ], 422);
        }

        if ($vendorStatus !== VendorStatus::Approved) {
            IdempotencyCompletion::fail($request, $idempotency);

            return response()->json([
                'message' => 'Vendor must be approved before invitation.',
                'errors' => [
                    'vendor_id' => ['Vendor must be approved before invitation.'],
                ],
            ], 422);
        }

        $selectedVendor = RequisitionSelectedVendor::query()
            ->where('tenant_id', $rfq->tenant_id)
            ->where('rfq_id', $rfq->id)
            ->where('vendor_id', $vendor->id)
            ->first();

        if ($selectedVendor === null) {
            IdempotencyCompletion::fail($request, $idempotency);

            return response()->json([
                'message' => 'Vendor must be selected before invitation.',
                'errors' => [
                    'vendor_id' => ['Vendor must be selected before invitation.'],
                ],
            ], 422);
        }

        $vendorEmail = $this->vendorEmail($vendor);
        $vendorName = $this->vendorName($vendor);

        $inv = new VendorInvitation();
        $inv->tenant_id = $tenantId;
        $inv->rfq_id = $rfq->id;
        $inv->vendor_id = $vendor->id;
        $inv->vendor_email = $vendorEmail;
        $inv->vendor_name = $vendorName;
        $inv->status = 'pending';
        $inv->invited_at = now();
        $inv->channel = $validator->validated()['channel'] ?? 'email';
        $inv->save();

        $response = response()->json([
            'data' => [
                'id' => $inv->id,
                'rfq_id' => $inv->rfq_id,
                'vendor_id' => $inv->vendor_id,
                'vendor_email' => $inv->vendor_email,
                'vendor_name' => $inv->vendor_name,
                'status' => $inv->status,
                'invited_at' => $inv->invited_at?->toAtomString(),
                'channel' => $inv->channel,
            ],
        ], 201);

        return IdempotencyCompletion::succeed($request, $idempotency, $response);
    }

    public function remind(Request $request, string $rfqId, string $invId, IdempotencyServiceInterface $idempotency): JsonResponse
    {
        try {
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

            $outcome = $this->rfqLifecycle->remindInvitation(new RemindRfqInvitationCommand(
                tenantId: $tenantId,
                rfqId: (string) $rfq->id,
                invitationId: $invId,
                requestedByPrincipalId: $this->userId($request),
            ));

            $invitation = VendorInvitation::query()
                ->where('tenant_id', $tenantId)
                ->where('rfq_id', $rfq->id)
                ->where('id', (string) $outcome->invitationId)
                ->first();

            if ($invitation === null) {
                IdempotencyCompletion::fail($request, $idempotency);

                return response()->json(['message' => 'Invitation not found'], 404);
            }

            $response = response()->json([
                'data' => [
                    'id' => $invitation->id,
                    'rfq_id' => $invitation->rfq_id,
                    'status' => $invitation->status,
                    'reminded_at' => $invitation->reminded_at?->toAtomString(),
                ],
            ]);

            return IdempotencyCompletion::succeed($request, $idempotency, $response);
        } catch (RfqLifecyclePreconditionException $e) {
            IdempotencyCompletion::fail($request, $idempotency);

            if ($e->isNotFound()) {
                return response()->json(['message' => 'Invitation not found'], 404);
            }

            return response()->json([
                'error' => 'Precondition failed',
                'message' => $e->getMessage(),
            ], 412);
        } catch (\Throwable $e) {
            IdempotencyCompletion::fail($request, $idempotency);
            throw $e;
        }
    }

    private function findVendor(string $tenantId, string $vendorId): ?Vendor
    {
        return Vendor::query()
            ->whereRaw('lower(tenant_id) = ?', [$this->normalizeIdentifier($tenantId)])
            ->whereRaw('lower(id) = ?', [$this->normalizeIdentifier($vendorId)])
            ->first();
    }

    private function vendorEmail(Vendor $vendor): ?string
    {
        foreach ([$vendor->primary_contact_email, $vendor->email] as $candidate) {
            $value = trim((string) $candidate);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function vendorName(Vendor $vendor): ?string
    {
        foreach ([$vendor->display_name, $vendor->legal_name, $vendor->name] as $candidate) {
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
