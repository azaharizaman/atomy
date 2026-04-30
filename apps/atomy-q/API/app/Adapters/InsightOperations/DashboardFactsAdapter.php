<?php

declare(strict_types=1);

namespace App\Adapters\InsightOperations;

use App\Models\Approval;
use App\Models\Award;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\VendorFinding;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;
use Nexus\InsightOperations\Contracts\DashboardFactsPortInterface;
use Nexus\InsightOperations\DTOs\DashboardFactsDto;
use Nexus\InsightOperations\DTOs\MetricFactDto;

final readonly class DashboardFactsAdapter implements
    DashboardFactsPortInterface
{
    public function factsForTenant(string $tenantId): DashboardFactsDto
    {
        $tenantId = trim($tenantId);
        $activeRfqs = $this->tenantRfqQuery($tenantId)
            ->whereNotIn("status", ["cancelled", "closed"])
            ->count();
        $pendingApprovals = Approval::query()
            ->where("tenant_id", $tenantId)
            ->where("status", "pending")
            ->count();
        $quoteIntakeCount = QuoteSubmission::query()
            ->where("tenant_id", $tenantId)
            ->whereIn("status", [
                "uploaded",
                "extracting",
                "normalizing",
                "needs_review",
            ])
            ->count();
        $awardsInFlight = Award::query()
            ->where("tenant_id", $tenantId)
            ->whereIn("status", ["pending", "protested"])
            ->count();
        $riskAlertCount = VendorFinding::query()
            ->where("tenant_id", $tenantId)
            ->whereIn("severity", ["high", "critical"])
            ->whereNotIn("status", ["closed", "resolved"])
            ->count();

        return new DashboardFactsDto(
            metrics: [
                new MetricFactDto("active_rfqs", $activeRfqs),
                new MetricFactDto("pending_approvals", $pendingApprovals),
                new MetricFactDto("quote_intake_count", $quoteIntakeCount),
                new MetricFactDto("awards_in_flight", $awardsInFlight),
                new MetricFactDto(
                    "total_savings",
                    $this->totalSavings($tenantId),
                ),
                $this->averageCycleTime($tenantId),
                new MetricFactDto("risk_alert_count", $riskAlertCount),
            ],
            recentActivity: $this->recentActivity($tenantId),
            riskAlerts: $this->riskAlerts($tenantId),
        );
    }

    private function tenantRfqQuery(
        string $tenantId,
    ): \Illuminate\Database\Eloquent\Builder {
        return Rfq::query()->where("tenant_id", $tenantId);
    }

    private function totalSavings(string $tenantId): float
    {
        $rfqs = $this->tenantRfqQuery($tenantId)->get([
            "estimated_value",
            "savings_percentage",
        ]);
        $total = 0.0;

        foreach ($rfqs as $rfq) {
            $total +=
                ((float) $rfq->estimated_value) *
                (((float) $rfq->savings_percentage) / 100);
        }

        return round($total, 2);
    }

    private function averageCycleTime(string $tenantId): MetricFactDto
    {
        if (!Schema::hasColumn("awards", "signoff_at")) {
            return new MetricFactDto(
                "avg_cycle_time_days",
                null,
                "not_available",
                "source_domain_not_implemented",
            );
        }

        $awards = Award::query()
            ->where("awards.tenant_id", $tenantId)
            ->join("rfqs", function ($join): void {
                $join
                    ->on("rfqs.id", "=", "awards.rfq_id")
                    ->whereColumn("rfqs.tenant_id", "awards.tenant_id");
            })
            ->whereNotNull("awards.signoff_at")
            ->get(["rfqs.created_at as rfq_created_at", "awards.signoff_at"]);

        if ($awards->isEmpty()) {
            return new MetricFactDto(
                "avg_cycle_time_days",
                null,
                "not_available",
                "source_domain_not_implemented",
            );
        }

        $totalDays = 0;
        $processedCount = 0;
        foreach ($awards as $award) {
            $createdAt = $this->dateOrNull($award->rfq_created_at);
            $signedAt = $this->dateOrNull($award->signoff_at);
            if ($createdAt === null || $signedAt === null) {
                continue;
            }

            $totalDays += abs($createdAt->diffInDays($signedAt));
            $processedCount++;
        }

        if ($processedCount === 0) {
            return new MetricFactDto(
                "avg_cycle_time_days",
                null,
                "not_available",
                "source_domain_not_available",
            );
        }

        return new MetricFactDto(
            "avg_cycle_time_days",
            round($totalDays / $processedCount, 2),
        );
    }

    private function dateOrNull(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === "") {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentActivity(string $tenantId): array
    {
        return Rfq::query()
            ->where("tenant_id", $tenantId)
            ->latest("updated_at")
            ->limit(5)
            ->get(["id", "rfq_number", "status", "updated_at"])
            ->map(
                static fn(Rfq $rfq): array => [
                    "type" => "rfq_status",
                    "rfq_id" => (string) $rfq->id,
                    "rfq_number" => (string) $rfq->rfq_number,
                    "status" => (string) $rfq->status,
                    "occurred_at" => $rfq->updated_at?->toAtomString(),
                ],
            )
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function riskAlerts(string $tenantId): array
    {
        return VendorFinding::query()
            ->where("tenant_id", $tenantId)
            ->whereIn("severity", ["high", "critical"])
            ->whereNotIn("status", ["closed", "resolved"])
            ->latest("created_at")
            ->limit(5)
            ->get(["id", "vendor_id", "severity", "status"])
            ->map(
                static fn(VendorFinding $finding): array => [
                    "finding_id" => (string) $finding->id,
                    "vendor_id" => (string) $finding->vendor_id,
                    "severity" => (string) $finding->severity,
                    "status" => (string) $finding->status,
                ],
            )
            ->values()
            ->all();
    }
}
