<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Adapters\Ai\Contracts\ProviderGovernanceClientInterface;
use App\Adapters\Ai\DTOs\GovernanceNarrativeRequest;
use App\Http\Controllers\Api\V1\Concerns\BuildsAiArtifactEnvelope;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithAiAvailability;
use App\Http\Controllers\Controller;
use App\Http\Idempotency\IdempotencyCompletion;
use App\Models\Vendor;
use App\Models\VendorEvidence;
use App\Models\VendorFinding;
use App\Services\VendorGovernanceScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Nexus\Common\Contracts\ClockInterface;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\Idempotency\Contracts\IdempotencyServiceInterface;
use Throwable;

final class VendorGovernanceController extends Controller
{
    use BuildsAiArtifactEnvelope;
    use ExtractsAuthContext;
    use InteractsWithAiAvailability;

    public function __construct(
        private readonly VendorGovernanceScoreService $scoreService,
        private readonly ProviderGovernanceClientInterface $governanceClient,
        private readonly ClockInterface $clock,
    ) {
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $vendor = $this->findVendor($tenantId, $id);
        if ($vendor === null) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $evidence = $this->evidenceQuery($tenantId, $id)->orderByDesc('observed_at')->orderBy('title')->get();
        $findings = $this->findingQuery($tenantId, $id)->orderByDesc('opened_at')->orderBy('issue_type')->get();
        $summary = $this->scoreService->summarize($evidence, $findings);
        $serializedEvidence = $evidence->map(fn (VendorEvidence $record): array => $this->serializeEvidence($record))->values()->all();
        $serializedFindings = $findings->map(fn (VendorFinding $record): array => $this->serializeFinding($record))->values()->all();

        return response()->json([
            'data' => [
                'vendor_id' => (string) $vendor->id,
                'evidence' => $serializedEvidence,
                'findings' => $serializedFindings,
                'summary_scores' => $summary['scores'],
                'warning_flags' => $summary['warning_flags'],
                'ai_narrative' => $this->readGovernanceNarrativeEnvelope(
                    tenantId: $tenantId,
                    vendorId: (string) $vendor->id,
                    evidence: $serializedEvidence,
                    findings: $serializedFindings,
                    summary: $summary,
                ),
            ],
        ]);
    }

    public function generate(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $vendor = $this->findVendor($tenantId, $id);
        if ($vendor === null) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $evidence = $this->evidenceQuery($tenantId, $id)->orderByDesc('observed_at')->orderBy('title')->get();
        $findings = $this->findingQuery($tenantId, $id)->orderByDesc('opened_at')->orderBy('issue_type')->get();
        $summary = $this->scoreService->summarize($evidence, $findings);
        $serializedEvidence = $evidence->map(fn (VendorEvidence $record): array => $this->serializeEvidence($record))->values()->all();
        $serializedFindings = $findings->map(fn (VendorFinding $record): array => $this->serializeFinding($record))->values()->all();

        return response()->json([
            'data' => [
                'vendor_id' => (string) $vendor->id,
                'evidence' => $serializedEvidence,
                'findings' => $serializedFindings,
                'summary_scores' => $summary['scores'],
                'warning_flags' => $summary['warning_flags'],
                'ai_narrative' => $this->buildGovernanceNarrativeEnvelope(
                    tenantId: $tenantId,
                    vendorId: (string) $vendor->id,
                    evidence: $serializedEvidence,
                    findings: $serializedFindings,
                    summary: $summary,
                ),
            ],
        ]);
    }

    public function dueDiligence(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $vendor = $this->findVendor($tenantId, $id);
        if ($vendor === null) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $items = $this->evidenceQuery($tenantId, $id)
            ->whereIn('domain', ['compliance', 'risk'])
            ->orderByDesc('observed_at')
            ->orderBy('title')
            ->get();

        $openFindings = $this->findingQuery($tenantId, $id)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        return response()->json([
            'data' => [
                'vendor_id' => (string) $vendor->id,
                'items' => $items->map(fn (VendorEvidence $record): array => $this->serializeEvidence($record))->values()->all(),
                'overall_status' => $openFindings > 0 ? 'attention_required' : 'current',
            ],
        ]);
    }

    public function updateDueDiligence(Request $request, string $id, string $itemId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $vendor = $this->findVendor($tenantId, $id);
        if ($vendor === null) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $validated = $request->validate([
            'review_status' => ['required', 'string', 'in:pending,reviewed,accepted,approved,rejected'],
            'reviewed_by' => ['nullable', 'string', 'max:128'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $record = $this->evidenceQuery($tenantId, $id)
            ->where('id', $this->normalizeIdentifier($itemId))
            ->first();
        if ($record === null) {
            return response()->json(['message' => 'Due diligence item not found'], 404);
        }

        $record->review_status = strtolower(trim((string) $validated['review_status']));
        $record->reviewed_by = array_key_exists('reviewed_by', $validated)
            ? $this->nullableString($validated['reviewed_by'])
            : $record->reviewed_by;
        $record->notes = array_key_exists('notes', $validated) ? $this->nullableString($validated['notes']) : $record->notes;
        $record->save();

        return response()->json([
            'data' => [
                'vendor_id' => (string) $vendor->id,
                'item_id' => (string) $record->id,
                'status' => (string) $record->review_status,
                'item' => $this->serializeEvidence($record),
            ],
        ]);
    }

    public function sanctionsScreening(
        Request $request,
        string $id,
        IdempotencyServiceInterface $idempotency,
    ): JsonResponse
    {
        try {
            $tenantId = $this->tenantId($request);
            $vendor = $this->findVendor($tenantId, $id);
            if ($vendor === null) {
                return response()->json(['message' => 'Vendor not found'], 404);
            }

            $validated = $request->validate([
                'title' => ['sometimes', 'string', 'max:255'],
                'source' => ['sometimes', 'string', 'max:128'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ]);

            $record = VendorEvidence::query()->create([
                'tenant_id' => $this->normalizeIdentifier($tenantId),
                'vendor_id' => $this->normalizeIdentifier((string) $vendor->id),
                'domain' => 'compliance',
                'type' => 'sanctions_screening',
                'title' => trim((string) ($validated['title'] ?? 'Manual sanctions screening')),
                'source' => trim((string) ($validated['source'] ?? 'manual')),
                'observed_at' => Carbon::now('UTC'),
                'expires_at' => Carbon::now('UTC')->addMonths(6),
                'review_status' => 'reviewed',
                'reviewed_by' => $this->userId($request),
                'notes' => $this->nullableString($validated['notes'] ?? null),
            ]);

            $response = response()->json([
                'data' => [
                    'vendor_id' => (string) $vendor->id,
                    'screening_status' => 'completed',
                    'matches' => [],
                    'evidence' => $this->serializeEvidence($record),
                ],
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

    public function sanctionsHistory(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $vendor = $this->findVendor($tenantId, $id);
        if ($vendor === null) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $history = $this->evidenceQuery($tenantId, $id)
            ->where('type', 'sanctions_screening')
            ->orderByDesc('observed_at')
            ->get();

        return response()->json([
            'data' => [
                'vendor_id' => (string) $vendor->id,
                'history' => $history->map(fn (VendorEvidence $record): array => $this->serializeEvidence($record))->values()->all(),
            ],
        ]);
    }

    private function findVendor(string $tenantId, string $vendorId): ?Vendor
    {
        return Vendor::query()
            ->where('tenant_id', $this->normalizeIdentifier($tenantId))
            ->where('id', $this->normalizeIdentifier($vendorId))
            ->first();
    }

    private function evidenceQuery(string $tenantId, string $vendorId): \Illuminate\Database\Eloquent\Builder
    {
        return VendorEvidence::query()
            ->where('tenant_id', $this->normalizeIdentifier($tenantId))
            ->where('vendor_id', $this->normalizeIdentifier($vendorId));
    }

    private function findingQuery(string $tenantId, string $vendorId): \Illuminate\Database\Eloquent\Builder
    {
        return VendorFinding::query()
            ->where('tenant_id', $this->normalizeIdentifier($tenantId))
            ->where('vendor_id', $this->normalizeIdentifier($vendorId));
    }

    private function serializeEvidence(VendorEvidence $record): array
    {
        return [
            'id' => (string) $record->id,
            'vendor_id' => (string) $record->vendor_id,
            'domain' => (string) $record->domain,
            'type' => (string) $record->type,
            'title' => (string) $record->title,
            'source' => (string) $record->source,
            'observed_at' => $record->observed_at?->toAtomString(),
            'expires_at' => $record->expires_at?->toAtomString(),
            'review_status' => (string) $record->review_status,
            'reviewed_by' => $record->reviewed_by,
            'notes' => $record->notes,
        ];
    }

    private function serializeFinding(VendorFinding $record): array
    {
        return [
            'id' => (string) $record->id,
            'vendor_id' => (string) $record->vendor_id,
            'domain' => (string) $record->domain,
            'issue_type' => (string) $record->issue_type,
            'severity' => (string) $record->severity,
            'status' => (string) $record->status,
            'opened_at' => $record->opened_at?->toAtomString(),
            'opened_by' => $record->opened_by,
            'remediation_owner' => $record->remediation_owner,
            'remediation_due_at' => $record->remediation_due_at?->toAtomString(),
            'resolution_summary' => $record->resolution_summary,
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

    /**
     * @param array<int, array<string, mixed>> $evidence
     * @param array<int, array<string, mixed>> $findings
     * @param array{scores: array<string, int>, warning_flags: list<string>} $summary
     * @return array<string, mixed>
     */
    private function buildGovernanceNarrativeEnvelope(
        string $tenantId,
        string $vendorId,
        array $evidence,
        array $findings,
        array $summary,
    ): array {
        [$sanitizedEvidence, $sanitizedFindings] = $this->sanitizeEvidenceAndFindings($evidence, $findings);

        if (! $this->aiCapabilityAvailable('governance_ai_narrative')) {
            return $this->unavailableArtifactEnvelope('governance_ai_narrative');
        }

        try {
            $narrative = $this->governanceClient->narrate(new GovernanceNarrativeRequest(
                featureKey: 'governance_ai_narrative',
                tenantId: $tenantId,
                vendorId: $vendorId,
                facts: [
                    'evidence' => $sanitizedEvidence,
                    'findings' => $sanitizedFindings,
                    'summary_scores' => $summary['scores'],
                    'warning_flags' => $summary['warning_flags'],
                ],
            ));
        } catch (Throwable $exception) {
            report($exception);

            return $this->unavailableArtifactEnvelope('governance_ai_narrative');
        }

        $artifact = $this->artifactEnvelope(
            featureKey: 'governance_ai_narrative',
            payload: $this->providerNarrativePayload($narrative, $sanitizedEvidence, $sanitizedFindings, $summary),
            provenance: $this->providerArtifactProvenance($narrative, AiStatusSchema::ENDPOINT_GROUP_GOVERNANCE),
        );

        Cache::put(
            $this->governanceNarrativeCacheKey($tenantId, $vendorId, $sanitizedEvidence, $sanitizedFindings, $summary),
            $artifact,
            now()->addMinutes(5),
        );

        return $artifact;
    }

    /**
     * @param array<int, array<string, mixed>> $evidence
     * @param array<int, array<string, mixed>> $findings
     * @param array{scores: array<string, int>, warning_flags: list<string>} $summary
     * @return array<string, mixed>
     */
    private function readGovernanceNarrativeEnvelope(
        string $tenantId,
        string $vendorId,
        array $evidence,
        array $findings,
        array $summary,
    ): array
    {
        [$sanitizedEvidence, $sanitizedFindings] = $this->sanitizeEvidenceAndFindings($evidence, $findings);

        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get($this->governanceNarrativeCacheKey($tenantId, $vendorId, $sanitizedEvidence, $sanitizedFindings, $summary));

        return is_array($cached) ? $cached : $this->unavailableArtifactEnvelope('governance_ai_narrative');
    }

    /**
     * @param array<int, array<string, mixed>> $evidence
     * @param array<int, array<string, mixed>> $findings
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function sanitizeEvidenceAndFindings(array $evidence, array $findings): array
    {
        $sanitizeActor = static function (mixed $value): ?string {
            if (! is_string($value) || trim($value) === '') {
                return null;
            }

            return hash('sha256', strtolower(trim($value)));
        };

        $sanitizedEvidence = array_map(static function (array $item) use ($sanitizeActor): array {
            return [
                'type' => $item['type'] ?? null,
                'domain' => $item['domain'] ?? null,
                'source' => $item['source'] ?? null,
                'review_status' => $item['review_status'] ?? null,
                'observed_at' => $item['observed_at'] ?? null,
                'expires_at' => $item['expires_at'] ?? null,
                'has_notes' => ($item['notes'] ?? null) !== null,
                'reviewed_by_hash' => $sanitizeActor($item['reviewed_by'] ?? null),
            ];
        }, $evidence);

        $sanitizedFindings = array_map(static function (array $item) use ($sanitizeActor): array {
            return [
                'domain' => $item['domain'] ?? null,
                'issue_type' => $item['issue_type'] ?? null,
                'severity' => $item['severity'] ?? null,
                'status' => $item['status'] ?? null,
                'opened_at' => $item['opened_at'] ?? null,
                'remediation_due_at' => $item['remediation_due_at'] ?? null,
                'has_resolution_summary' => ($item['resolution_summary'] ?? null) !== null,
                'opened_by_hash' => $sanitizeActor($item['opened_by'] ?? null),
                'remediation_owner_hash' => $sanitizeActor($item['remediation_owner'] ?? null),
            ];
        }, $findings);

        return [$sanitizedEvidence, $sanitizedFindings];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, array<string, mixed>> $evidence
     * @param array<int, array<string, mixed>> $findings
     * @param array{scores: array<string, int>, warning_flags: list<string>} $summary
     * @return array<string, mixed>
     */
    private function providerNarrativePayload(array $payload, array $evidence, array $findings, array $summary): array
    {
        $narrative = $this->unwrapProviderPayload($payload);

        $narrative['source_facts'] = [
            'evidence' => $evidence,
            'findings' => $findings,
            'summary_scores' => $summary['scores'],
            'warning_flags' => $summary['warning_flags'],
        ];

        return $narrative;
    }

    /**
     * @param array<int, array<string, mixed>> $evidence
     * @param array<int, array<string, mixed>> $findings
     * @param array{scores: array<string, int>, warning_flags: list<string>} $summary
     */
    private function governanceNarrativeCacheKey(
        string $tenantId,
        string $vendorId,
        array $evidence,
        array $findings,
        array $summary,
    ): string
    {
        $encoded = json_encode([
            'evidence' => $evidence,
            'findings' => $findings,
            'summary_scores' => $summary['scores'],
            'warning_flags' => $summary['warning_flags'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return 'ai:vendor-governance:' . strtolower(trim($tenantId)) . ':' . strtolower(trim($vendorId)) . ':' . hash(
            'sha256',
            is_string($encoded) ? $encoded : serialize([$evidence, $findings, $summary]),
        );
    }

    protected function aiArtifactCapabilityGroup(): string
    {
        return AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE;
    }

    protected function aiArtifactEndpointGroup(): string
    {
        return AiStatusSchema::ENDPOINT_GROUP_GOVERNANCE;
    }
}
