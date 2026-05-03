<?php

declare(strict_types=1);

namespace App\Services\EvidenceVault;

use RuntimeException;

use Illuminate\Support\Facades\DB;

use App\Models\EvidenceBundle;
use App\Models\EvidenceBundleItem;
use App\Models\Rfq;
use App\Models\User;
use App\Services\QuoteIntake\DecisionTrailRecorder;

final readonly class AwardEvidencePackFinalizer
{
    public function __construct(
        private EvidenceVaultSummaryService $summaryService,
        private DecisionTrailRecorder $decisionTrailRecorder,
    ) {
    }

    public function finalize(string $tenantId, Rfq $rfq, User $actor): EvidenceBundle
    {
        $summary = $this->summaryService->summarize($tenantId, $rfq);
        $readiness = $summary['readiness'] ?? [];

        if (($readiness['ready'] ?? false) !== true) {
            throw new EvidencePackNotReadyException($this->blockers($readiness));
        }

        return DB::transaction(function () use ($tenantId, $rfq, $actor, $summary): EvidenceBundle {
            $rfqId = (string) $rfq->id;
            $version = $this->nextVersion($tenantId, $rfqId);
            $comparisonRunId = $this->sectionItemId($summary, 'final_comparison');
            $approvalId = $this->sectionItemId($summary, 'approval_trail');
            $awardId = $this->sectionItemId($summary, 'award_signoff');

            EvidenceBundle::query()
                ->where('tenant_id', $tenantId)
                ->where('rfq_id', $rfqId)
                ->where('type', 'award_justification')
                ->where('status', 'finalized')
                ->update(['status' => 'superseded']);

            $bundle = EvidenceBundle::query()->create([
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqId,
                'comparison_run_id' => $comparisonRunId,
                'approval_id' => $approvalId,
                'award_id' => $awardId,
                'type' => 'award_justification',
                'status' => 'draft',
                'version' => $version,
                'manifest' => null,
                'checksum' => null,
                'finalized_at' => null,
                'created_by' => $actor->id,
            ]);

            $includedAt = now();
            foreach ($this->includedSources($summary) as $source) {
                EvidenceBundleItem::query()->create([
                    'tenant_id' => $tenantId,
                    'evidence_bundle_id' => $bundle->id,
                    'source_type' => $source['source_type'],
                    'source_id' => $source['source_id'],
                    'artifact_kind' => $source['artifact_kind'],
                    'label' => $source['label'],
                    'storage_path' => null,
                    'checksum' => null,
                    'metadata' => $source['metadata'],
                    'included_at' => $includedAt,
                ]);
            }

            $manifest = [
                'bundle' => [
                    'id' => (string) $bundle->id,
                    'type' => 'award_justification',
                    'version' => $version,
                    'status' => 'finalized',
                ],
                'rfq' => $summary['rfq'],
                'summary' => $summary,
                'generated' => [
                    'actor_id' => (string) $actor->id,
                    'at' => $includedAt->toAtomString(),
                ],
            ];
            $manifestJson = json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $checksum = hash('sha256', $manifestJson);

            $bundle->forceFill([
                'status' => 'finalized',
                'manifest' => $manifest,
                'checksum' => $checksum,
                'finalized_at' => $includedAt,
            ])->save();

            $this->decisionTrailRecorder->recordEvidencePackFinalized(
                tenantId: $tenantId,
                rfqId: $rfqId,
                bundleId: (string) $bundle->id,
                checksum: $checksum,
                actorId: (string) $actor->id,
            );

            return $bundle->refresh();
        });
    }

    private function nextVersion(string $tenantId, string $rfqId): int
    {
        $latestVersion = EvidenceBundle::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId)
            ->where('type', 'award_justification')
            ->lockForUpdate()
            ->max('version');

        return ((int) ($latestVersion ?? 0)) + 1;
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function sectionItemId(array $summary, string $sectionCode): ?string
    {
        foreach (($summary['sections'] ?? []) as $section) {
            if (($section['code'] ?? null) !== $sectionCode) {
                continue;
            }

            $item = ($section['items'] ?? [])[0] ?? null;
            $id = is_array($item) ? ($item['id'] ?? null) : null;

            return $id === null ? null : (string) $id;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $readiness
     * @return list<array{code: string, message: string}>
     */
    private function blockers(array $readiness): array
    {
        return array_values(array_filter(
            $readiness['blockers'] ?? [],
            static fn (mixed $blocker): bool => is_array($blocker),
        ));
    }

    /**
     * @param array<string, mixed> $summary
     * @return list<array{source_type: string, source_id: string|null, artifact_kind: string, label: string, metadata: array<string, mixed>}>
     */
    private function includedSources(array $summary): array
    {
        $sources = [];

        foreach (($summary['sections'] ?? []) as $section) {
            if (! is_array($section)) {
                continue;
            }

            $code = (string) ($section['code'] ?? 'unknown');
            $sources[] = [
                'source_type' => $this->sourceType($code),
                'source_id' => $this->sectionItemId($summary, $code),
                'artifact_kind' => $code,
                'label' => (string) ($section['label'] ?? $code),
                'metadata' => $section,
            ];
        }

        return $sources;
    }

    private function sourceType(string $sectionCode): string
    {
        return match ($sectionCode) {
            'quote_sources' => 'quote_submission',
            'final_comparison' => 'comparison_run',
            'approval_trail' => 'approval',
            'award_signoff' => 'award',
            default => 'evidence_summary',
        };
    }
}

final class EvidencePackNotReadyException extends RuntimeException
{
    /**
     * @param list<array{code: string, message: string}> $blockers
     */
    public function __construct(private readonly array $blockers)
    {
        parent::__construct('Evidence pack is not ready for finalization.');
    }

    /**
     * @return list<array{code: string, message: string}>
     */
    public function blockers(): array
    {
        return $this->blockers;
    }
}
