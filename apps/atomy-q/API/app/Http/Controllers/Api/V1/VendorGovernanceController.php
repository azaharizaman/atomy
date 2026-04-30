<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Http\Idempotency\IdempotencyCompletion;
use App\Models\Vendor;
use App\Models\VendorEvidence;
use App\Models\VendorFinding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Nexus\Idempotency\Contracts\IdempotencyServiceInterface;
use Nexus\InsightOperations\Contracts\GovernanceNarrativeCoordinatorInterface;

final class VendorGovernanceController extends Controller
{
    use ExtractsAuthContext;

    public function __construct(
        private readonly GovernanceNarrativeCoordinatorInterface $governanceNarrativeCoordinator,
    ) {}

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $vendor = $this->findVendor($tenantId, $id);
        if ($vendor === null) {
            return response()->json(["message" => "Vendor not found"], 404);
        }

        return response()->json(
            $this->governanceNarrativeCoordinator
                ->show($tenantId, (string) $vendor->id)
                ->toResponseArray(),
        );
    }

    public function generate(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $vendor = $this->findVendor($tenantId, $id);
        if ($vendor === null) {
            return response()->json(["message" => "Vendor not found"], 404);
        }

        return response()->json(
            $this->governanceNarrativeCoordinator
                ->generate(
                    $tenantId,
                    (string) $vendor->id,
                    $this->userId($request),
                )
                ->toResponseArray(),
        );
    }

    public function dueDiligence(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $vendor = $this->findVendor($tenantId, $id);
        if ($vendor === null) {
            return response()->json(["message" => "Vendor not found"], 404);
        }

        $items = $this->evidenceQuery($tenantId, $id)
            ->whereIn("domain", ["compliance", "risk"])
            ->orderByDesc("observed_at")
            ->orderBy("title")
            ->get();

        $openFindings = $this->findingQuery($tenantId, $id)
            ->whereIn("status", ["open", "in_progress"])
            ->count();

        return response()->json([
            "data" => [
                "vendor_id" => (string) $vendor->id,
                "items" => $items
                    ->map(
                        fn(
                            VendorEvidence $record,
                        ): array => $this->serializeEvidence($record),
                    )
                    ->values()
                    ->all(),
                "overall_status" =>
                    $openFindings > 0 ? "attention_required" : "current",
            ],
        ]);
    }

    public function updateDueDiligence(
        Request $request,
        string $id,
        string $itemId,
    ): JsonResponse {
        $tenantId = $this->tenantId($request);
        $vendor = $this->findVendor($tenantId, $id);
        if ($vendor === null) {
            return response()->json(["message" => "Vendor not found"], 404);
        }

        $validated = $request->validate([
            "review_status" => [
                "required",
                "string",
                "in:pending,reviewed,accepted,approved,rejected",
            ],
            "reviewed_by" => ["nullable", "string", "max:128"],
            "notes" => ["nullable", "string", "max:2000"],
        ]);

        $record = $this->evidenceQuery($tenantId, $id)
            ->where("id", $this->normalizeIdentifier($itemId))
            ->first();
        if ($record === null) {
            return response()->json(
                ["message" => "Due diligence item not found"],
                404,
            );
        }

        $record->review_status = strtolower(
            trim((string) $validated["review_status"]),
        );
        $record->reviewed_by = array_key_exists("reviewed_by", $validated)
            ? $this->nullableString($validated["reviewed_by"])
            : $record->reviewed_by;
        $record->notes = array_key_exists("notes", $validated)
            ? $this->nullableString($validated["notes"])
            : $record->notes;
        $record->save();

        return response()->json([
            "data" => [
                "vendor_id" => (string) $vendor->id,
                "item_id" => (string) $record->id,
                "status" => (string) $record->review_status,
                "item" => $this->serializeEvidence($record),
            ],
        ]);
    }

    public function sanctionsScreening(
        Request $request,
        string $id,
        IdempotencyServiceInterface $idempotency,
    ): JsonResponse {
        try {
            $tenantId = $this->tenantId($request);
            $vendor = $this->findVendor($tenantId, $id);
            if ($vendor === null) {
                return response()->json(["message" => "Vendor not found"], 404);
            }

            $validated = $request->validate([
                "title" => ["sometimes", "string", "max:255"],
                "source" => ["sometimes", "string", "max:128"],
                "notes" => ["nullable", "string", "max:2000"],
            ]);

            $record = VendorEvidence::query()->create([
                "tenant_id" => $tenantId,
                "vendor_id" => (string) $vendor->id,
                "domain" => "compliance",
                "type" => "sanctions_screening",
                "title" => trim(
                    (string) ($validated["title"] ??
                        "Manual sanctions screening"),
                ),
                "source" => trim((string) ($validated["source"] ?? "manual")),
                "observed_at" => Carbon::now("UTC"),
                "expires_at" => Carbon::now("UTC")->addMonths(6),
                "review_status" => "reviewed",
                "reviewed_by" => $this->userId($request),
                "notes" => $this->nullableString($validated["notes"] ?? null),
            ]);

            $response = response()->json(
                [
                    "data" => [
                        "vendor_id" => (string) $vendor->id,
                        "screening_status" => "completed",
                        "matches" => [],
                        "evidence" => $this->serializeEvidence($record),
                    ],
                ],
                201,
            );

            return IdempotencyCompletion::succeed(
                $request,
                $idempotency,
                $response,
            );
        } catch (ValidationException $exception) {
            IdempotencyCompletion::fail($request, $idempotency);
            throw $exception;
        } catch (\Throwable $exception) {
            IdempotencyCompletion::fail($request, $idempotency);
            throw $exception;
        }
    }

    public function sanctionsHistory(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $vendor = $this->findVendor($tenantId, $id);
        if ($vendor === null) {
            return response()->json(["message" => "Vendor not found"], 404);
        }

        $history = $this->evidenceQuery($tenantId, $id)
            ->where("type", "sanctions_screening")
            ->orderByDesc("observed_at")
            ->get();

        $historyRows = $history
            ->map(
                fn(VendorEvidence $record): array => $this->serializeEvidence(
                    $record,
                ),
            )
            ->values()
            ->all();

        return response()->json([
            "data" => [
                "vendor_id" => (string) $vendor->id,
                "history" => $historyRows,
                "screening_status" =>
                    $historyRows === []
                        ? "manual_review_required"
                        : "completed",
                "reason_code" =>
                    $historyRows === []
                        ? "sanctions_provider_not_configured"
                        : null,
            ],
        ]);
    }

    private function findVendor(string $tenantId, string $vendorId): ?Vendor
    {
        return Vendor::query()
            ->where("tenant_id", $tenantId)
            ->where("id", $vendorId)
            ->first();
    }

    private function evidenceQuery(
        string $tenantId,
        string $vendorId,
    ): \Illuminate\Database\Eloquent\Builder {
        return VendorEvidence::query()
            ->where("tenant_id", $tenantId)
            ->where("vendor_id", $vendorId);
    }

    private function findingQuery(
        string $tenantId,
        string $vendorId,
    ): \Illuminate\Database\Eloquent\Builder {
        return VendorFinding::query()
            ->where("tenant_id", $tenantId)
            ->where("vendor_id", $vendorId);
    }

    private function serializeEvidence(VendorEvidence $record): array
    {
        return [
            "id" => (string) $record->id,
            "vendor_id" => (string) $record->vendor_id,
            "domain" => (string) $record->domain,
            "type" => (string) $record->type,
            "title" => (string) $record->title,
            "source" => (string) $record->source,
            "observed_at" => $record->observed_at?->toAtomString(),
            "expires_at" => $record->expires_at?->toAtomString(),
            "review_status" => (string) $record->review_status,
            "reviewed_by" => $record->reviewed_by,
            "notes" => $record->notes,
        ];
    }

    private function serializeFinding(VendorFinding $record): array
    {
        return [
            "id" => (string) $record->id,
            "vendor_id" => (string) $record->vendor_id,
            "domain" => (string) $record->domain,
            "issue_type" => (string) $record->issue_type,
            "severity" => (string) $record->severity,
            "status" => (string) $record->status,
            "opened_at" => $record->opened_at?->toAtomString(),
            "opened_by" => $record->opened_by,
            "remediation_owner" => $record->remediation_owner,
            "remediation_due_at" => $record->remediation_due_at?->toAtomString(),
            "resolution_summary" => $record->resolution_summary,
        ];
    }

    private function normalizeIdentifier(string $value): string
    {
        return strtolower(trim($value));
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === "" ? null : $normalized;
    }
}
