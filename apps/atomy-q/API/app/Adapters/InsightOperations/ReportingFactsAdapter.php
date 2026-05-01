<?php

declare(strict_types=1);

namespace App\Adapters\InsightOperations;

use App\Models\Award;
use App\Models\Rfq;
use Nexus\InsightOperations\Contracts\ReportingFactsPortInterface;
use Nexus\InsightOperations\DTOs\MetricFactDto;
use Nexus\InsightOperations\DTOs\ReportingFactsDto;

/**
 * Provides tenant-scoped reporting facts for KPI, trend, and category summaries.
 *
 * Unsupported subjects return an explicit unavailable metric instead of empty
 * success data, allowing insight consumers to distinguish missing domains from
 * real zero-valued reports.
 */
final readonly class ReportingFactsAdapter implements ReportingFactsPortInterface
{
    public function factsForTenant(string $tenantId, string $subjectType): ReportingFactsDto
    {
        $tenantId = trim($tenantId);

        return match ($subjectType) {
            'report_kpis' => $this->kpiFacts($tenantId, $subjectType),
            'report_spend_trend' => $this->spendTrendFacts($tenantId, $subjectType),
            'report_spend_by_category' => $this->spendByCategoryFacts($tenantId, $subjectType),
            default => new ReportingFactsDto(
                subjectType: $subjectType,
                metrics: [
                    new MetricFactDto('report_dataset', null, 'not_available', 'source_domain_not_implemented'),
                ],
            ),
        };
    }

    private function kpiFacts(string $tenantId, string $subjectType): ReportingFactsDto
    {
        $totalSpend = $this->awardQuery($tenantId)->sum('amount');
        $activeRfqs = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->whereNotIn('status', ['cancelled', 'closed'])
            ->count();

        return new ReportingFactsDto(
            subjectType: $subjectType,
            metrics: [
                new MetricFactDto('total_spend', round((float) $totalSpend, 2)),
                new MetricFactDto('active_rfqs', $activeRfqs),
                new MetricFactDto('savings', $this->savings($tenantId)),
            ],
        );
    }

    private function spendTrendFacts(string $tenantId, string $subjectType): ReportingFactsDto
    {
        $series = $this->awardQuery($tenantId)
            ->get()
            ->groupBy(static fn (Award $award): string => $award->created_at?->format('Y-m') ?? 'undated')
            ->map(static fn ($awards, string $period): array => [
                'period' => $period,
                'spend' => round((float) $awards->sum('amount'), 2),
            ])
            ->sortBy('period')
            ->values()
            ->all();

        return new ReportingFactsDto(
            subjectType: $subjectType,
            metrics: [
                new MetricFactDto('period', 'monthly'),
                new MetricFactDto('total_spend', array_sum(array_map(
                    static fn (array $row): float => (float) $row['spend'],
                    $series,
                ))),
            ],
            series: $series,
        );
    }

    private function spendByCategoryFacts(string $tenantId, string $subjectType): ReportingFactsDto
    {
        $categories = $this->awardQuery($tenantId)
            ->join('rfqs', function ($join): void {
                $join->on('rfqs.id', '=', 'awards.rfq_id')
                    ->whereColumn('rfqs.tenant_id', 'awards.tenant_id');
            })
            ->selectRaw("coalesce(nullif(rfqs.category, ''), 'Uncategorized') as category")
            ->selectRaw('sum(awards.amount) as spend')
            ->groupBy('category')
            ->orderBy('category')
            ->get()
            ->map(static fn (object $row): array => [
                'category' => (string) $row->category,
                'spend' => round((float) $row->spend, 2),
            ])
            ->values()
            ->all();

        return new ReportingFactsDto(
            subjectType: $subjectType,
            metrics: [
                new MetricFactDto('categories', $categories),
                new MetricFactDto('total_spend', array_sum(array_map(
                    static fn (array $row): float => (float) $row['spend'],
                    $categories,
                ))),
            ],
            rows: $categories,
        );
    }

    private function awardQuery(string $tenantId): \Illuminate\Database\Eloquent\Builder
    {
        return Award::query()
            ->where('awards.tenant_id', $tenantId)
            ->where('awards.status', 'signed_off');
    }

    private function savings(string $tenantId): float
    {
        $rfqs = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->get(['estimated_value', 'savings_percentage']);
        $total = 0.0;

        foreach ($rfqs as $rfq) {
            $total += ((float) $rfq->estimated_value) * (((float) $rfq->savings_percentage) / 100);
        }

        return round($total, 2);
    }
}
