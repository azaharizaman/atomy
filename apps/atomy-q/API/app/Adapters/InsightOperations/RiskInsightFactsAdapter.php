<?php

declare(strict_types=1);

namespace App\Adapters\InsightOperations;

use App\Models\QuoteSubmission;
use App\Models\RequisitionSelectedVendor;
use App\Models\Rfq;
use App\Models\RiskItem;
use App\Models\VendorFinding;
use App\Models\VendorInvitation;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Nexus\InsightOperations\Contracts\RiskInsightFactsPortInterface;
use Nexus\InsightOperations\DTOs\RiskInsightFactsDto;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Throwable;

final readonly class RiskInsightFactsAdapter implements
    RiskInsightFactsPortInterface
{
    public function factsForRfq(
        string $tenantId,
        string $rfqId,
    ): RiskInsightFactsDto {
        $tenantId = trim($tenantId);
        $rfqId = trim($rfqId);
        $rfq = Rfq::query()
            ->where("tenant_id", $tenantId)
            ->where("id", $rfqId)
            ->first();

        if (!$rfq instanceof Rfq) {
            return new RiskInsightFactsDto($rfqId, [], $this->manualReview([]));
        }

        $items = [
            ...$this->deadlineRiskItems($rfq),
            ...$this->vendorFindingRiskItems($tenantId, $rfqId),
            ...$this->quoteReadinessRiskItems($tenantId, $rfqId),
        ];

        usort($items, static function (array $left, array $right): int {
            $severityRank = [
                "critical" => 0,
                "high" => 1,
                "medium" => 2,
                "low" => 3,
            ];

            return [$severityRank[$left["severity"]] ?? 4, $left["title"]] <=> [
                $severityRank[$right["severity"]] ?? 4,
                $right["title"],
            ];
        });

        return new RiskInsightFactsDto(
            $rfqId,
            $items,
            $this->manualReview($items),
        );
    }

    public function escalate(
        string $tenantId,
        string $rfqId,
        string $itemId,
    ): void {
        $query = RiskItem::query()
            ->where("tenant_id", strtolower(trim($tenantId)))
            ->where("id", strtolower(trim($itemId)));

        if ($rfqId !== "") {
            $query->where("rfq_id", strtolower(trim($rfqId)));
        }

        if ($query->update(["status" => "escalated"]) === 0) {
            $exception = new ModelNotFoundException();
            $exception->setModel(RiskItem::class, [$itemId]);
            throw $exception;
        }
    }

    public function resolveAsException(
        string $tenantId,
        string $rfqId,
        string $itemId,
        string $actorId,
    ): void {
        $query = RiskItem::query()
            ->where("tenant_id", strtolower(trim($tenantId)))
            ->where("id", strtolower(trim($itemId)));

        if ($rfqId !== "") {
            $query->where("rfq_id", strtolower(trim($rfqId)));
        }

        if (
            $query->update([
                "status" => "exception",
                "resolved_at" => CarbonImmutable::now("UTC"),
                "resolved_by" => $actorId,
            ]) === 0
        ) {
            $exception = new ModelNotFoundException();
            $exception->setModel(RiskItem::class, [$itemId]);
            throw $exception;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function deadlineRiskItems(Rfq $rfq): array
    {
        $now = CarbonImmutable::now("UTC");
        $items = [];

        foreach (
            [
                "submission_deadline" => "Submission deadline",
                "closing_date" => "RFQ closing date",
            ]
            as $field => $label
        ) {
            $date = $this->dateOrNull($rfq->getAttribute($field));
            if (!$date instanceof CarbonInterface) {
                continue;
            }

            if ($date->isPast()) {
                $items[] = $this->riskItem(
                    domain: "risk",
                    severity: "high",
                    status: "open",
                    title: $label . " has passed",
                    source: "rfq_schedule",
                    sourceId: (string) $rfq->id,
                    details: [
                        "field" => $field,
                        "due_at" => $date->toAtomString(),
                        "days_overdue" => $date->diffInDays($now),
                    ],
                );
                continue;
            }

            if ($date->lessThanOrEqualTo($now->addDays(3))) {
                $items[] = $this->riskItem(
                    domain: "risk",
                    severity: "medium",
                    status: "open",
                    title: $label . " is due within 3 days",
                    source: "rfq_schedule",
                    sourceId: (string) $rfq->id,
                    details: [
                        "field" => $field,
                        "due_at" => $date->toAtomString(),
                        "days_remaining" => $now->diffInDays($date),
                    ],
                );
            }
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function vendorFindingRiskItems(
        string $tenantId,
        string $rfqId,
    ): array {
        $vendorIds = $this->vendorIdsForRfq($tenantId, $rfqId);
        if ($vendorIds === []) {
            return [];
        }

        return VendorFinding::query()
            ->where("tenant_id", $tenantId)
            ->whereIn("vendor_id", $vendorIds)
            ->whereIn("status", ["open", "in_progress"])
            ->whereIn("severity", ["critical", "high", "medium"])
            ->orderByRaw(
                "case severity when 'critical' then 0 when 'high' then 1 when 'medium' then 2 else 3 end",
            )
            ->orderByDesc("opened_at")
            ->get()
            ->map(
                fn(VendorFinding $finding): array => $this->riskItem(
                    domain: (string) $finding->domain,
                    severity: (string) $finding->severity,
                    status: (string) $finding->status,
                    title: "Vendor finding: " .
                        str_replace("_", " ", (string) $finding->issue_type),
                    source: "vendor_finding",
                    sourceId: (string) $finding->id,
                    details: [
                        "vendor_id" => (string) $finding->vendor_id,
                        "issue_type" => (string) $finding->issue_type,
                        "opened_at" => $finding->opened_at?->toAtomString(),
                        "remediation_due_at" => $finding->remediation_due_at?->toAtomString(),
                    ],
                ),
            )
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function quoteReadinessRiskItems(
        string $tenantId,
        string $rfqId,
    ): array {
        return QuoteSubmission::query()
            ->where("tenant_id", $tenantId)
            ->where("rfq_id", $rfqId)
            ->whereNotIn("status", ["ready", "completed"])
            ->orderBy("vendor_name")
            ->get()
            ->map(
                fn(QuoteSubmission $submission): array => $this->riskItem(
                    domain: "risk",
                    severity: $this->quoteSeverity($submission),
                    status: "open",
                    title: "Quote is not comparison-ready",
                    source: "quote_submission",
                    sourceId: (string) $submission->id,
                    details: [
                        "vendor_id" => $submission->vendor_id,
                        "vendor_name" => $submission->vendor_name,
                        "quote_status" => (string) $submission->status,
                        "warnings_count" => max(
                            (int) ($submission->warnings_count ?? 0),
                            0,
                        ),
                        "errors_count" => max(
                            (int) ($submission->errors_count ?? 0),
                            0,
                        ),
                    ],
                ),
            )
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function vendorIdsForRfq(string $tenantId, string $rfqId): array
    {
        $ids = collect();

        $ids = $ids->merge(
            RequisitionSelectedVendor::query()
                ->where("tenant_id", $tenantId)
                ->where("rfq_id", $rfqId)
                ->pluck("vendor_id"),
        );
        $ids = $ids->merge(
            VendorInvitation::query()
                ->where("tenant_id", $tenantId)
                ->where("rfq_id", $rfqId)
                ->whereNotNull("vendor_id")
                ->pluck("vendor_id"),
        );
        $ids = $ids->merge(
            QuoteSubmission::query()
                ->where("tenant_id", $tenantId)
                ->where("rfq_id", $rfqId)
                ->whereNotNull("vendor_id")
                ->pluck("vendor_id"),
        );

        /** @var Collection<int, mixed> $ids */
        return $ids
            ->map(static fn(mixed $value): string => trim((string) $value))
            ->filter(static fn(string $value): bool => $value !== "")
            ->unique()
            ->values()
            ->all();
    }

    private function quoteSeverity(QuoteSubmission $submission): string
    {
        if (
            (string) $submission->status === "failed" ||
            (int) ($submission->errors_count ?? 0) > 0
        ) {
            return "high";
        }

        return (string) $submission->status === "needs_review"
            ? "medium"
            : "low";
    }

    /**
     * @param array<string, mixed> $details
     * @return array<string, mixed>
     */
    private function riskItem(
        string $domain,
        string $severity,
        string $status,
        string $title,
        string $source,
        string $sourceId,
        array $details,
    ): array {
        return [
            "domain" => $domain,
            "severity" => $severity,
            "status" => $status,
            "title" => $title,
            "source" => $source,
            "source_id" => $sourceId,
            "details" => $details,
        ];
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array<string, mixed>
     */
    private function manualReview(array $items): array
    {
        return [
            "feature_key" => "governance_manual_review",
            "capability_group" =>
                AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
            "available" => true,
            "status" => AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
            "manual_continuity" => "available",
            "reason_codes" => ["ai_not_required"],
            "diagnostics" => ["mode" => "deterministic"],
            "pending_items" => count($items),
        ];
    }

    private function dateOrNull(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof CarbonImmutable) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        $text = trim((string) $value);
        if ($text === "") {
            return null;
        }

        try {
            return CarbonImmutable::parse($text);
        } catch (Throwable) {
            return null;
        }
    }
}
