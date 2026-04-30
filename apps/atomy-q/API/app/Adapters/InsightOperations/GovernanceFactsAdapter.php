<?php

declare(strict_types=1);

namespace App\Adapters\InsightOperations;

use App\Models\VendorEvidence;
use App\Models\VendorFinding;
use App\Services\VendorGovernanceScoreService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Nexus\InsightOperations\Contracts\GovernanceFactsPortInterface;
use Nexus\InsightOperations\DTOs\GovernanceFactsDto;

final readonly class GovernanceFactsAdapter implements GovernanceFactsPortInterface
{
    public function __construct(private VendorGovernanceScoreService $scoreService)
    {
    }

    public function factsForVendor(string $tenantId, string $vendorId): GovernanceFactsDto
    {
        $tenantId = trim($tenantId);
        $vendorId = trim($vendorId);
        $evidence = $this->evidenceQuery($tenantId, $vendorId)
            ->orderByDesc('observed_at')
            ->orderBy('title')
            ->get();
        $findings = $this->findingQuery($tenantId, $vendorId)
            ->orderByDesc('opened_at')
            ->orderBy('issue_type')
            ->get();
        $summary = $this->scoreService->summarize($evidence, $findings);
        $sanctionsScreenings = $evidence
            ->filter(static fn (VendorEvidence $record): bool => (string) $record->type === 'sanctions_screening')
            ->map(fn (VendorEvidence $record): array => $this->serializeEvidence($record))
            ->values()
            ->all();

        return new GovernanceFactsDto(
            vendorId: $vendorId,
            evidence: $evidence->map(fn (VendorEvidence $record): array => $this->serializeEvidence($record))->values()->all(),
            findings: $findings->map(fn (VendorFinding $record): array => $this->serializeFinding($record))->values()->all(),
            scores: $summary['scores'],
            warningFlags: $summary['warning_flags'],
            sanctionsScreenings: $sanctionsScreenings,
            dueDiligenceStatus: $this->dueDiligenceStatus($evidence, $findings),
            evidenceFreshness: $this->evidenceFreshness($evidence),
        );
    }

    private function evidenceQuery(string $tenantId, string $vendorId): \Illuminate\Database\Eloquent\Builder
    {
        return VendorEvidence::query()
            ->where('tenant_id', $tenantId)
            ->where('vendor_id', $vendorId);
    }

    private function findingQuery(string $tenantId, string $vendorId): \Illuminate\Database\Eloquent\Builder
    {
        return VendorFinding::query()
            ->where('tenant_id', $tenantId)
            ->where('vendor_id', $vendorId);
    }

    /**
     * @return array<string, mixed>
     */
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
            'has_notes' => $record->notes !== null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
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
            'has_resolution_summary' => $record->resolution_summary !== null,
        ];
    }

    /**
     * @param Collection<int, VendorEvidence> $evidence
     * @param Collection<int, VendorFinding> $findings
     */
    private function dueDiligenceStatus(Collection $evidence, Collection $findings): string
    {
        $openFindings = $findings->filter(static fn (VendorFinding $record): bool => in_array((string) $record->status, ['open', 'in_progress'], true));
        if ($openFindings->isNotEmpty()) {
            return 'attention_required';
        }

        $dueDiligenceEvidence = $evidence->filter(static fn (VendorEvidence $record): bool => in_array((string) $record->domain, ['compliance', 'risk'], true));
        if ($dueDiligenceEvidence->isEmpty()) {
            return 'pending';
        }

        return 'current';
    }

    /**
     * @param Collection<int, VendorEvidence> $evidence
     * @return array<string, mixed>
     */
    private function evidenceFreshness(Collection $evidence): array
    {
        $now = CarbonImmutable::now('UTC');
        $isExpired = static fn(VendorEvidence $record): bool => $record->expires_at !==
            null && $record->expires_at->isBefore($now);
        $isStale = static fn(VendorEvidence $record): bool => $record->observed_at ===
            null || $record->observed_at->diffInDays($now) > 365;

        $expired = $evidence->filter($isExpired)->count();
        $stale = $evidence
            ->filter(
                static fn(VendorEvidence $record): bool => !$isExpired($record) &&
                    $isStale($record),
            )
            ->count();

        return [
            'total' => $evidence->count(),
            'expired' => $expired,
            'stale' => $stale,
            'current' => max($evidence->count() - $expired - $stale, 0),
        ];
    }
}
