<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\ProcurementOperations\Contracts\VendorRecommendationCoordinatorInterface;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationCandidate;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationScoredCandidate;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\Vendor;

final class VendorRecommendationController extends Controller
{
    use ExtractsAuthContext;

    public function store(
        Request $request,
        string $rfqId,
        VendorRecommendationCoordinatorInterface $coordinator,
    ): JsonResponse {
        $tenantId = $this->tenantId($request);
        $rfq = $this->findRfq($tenantId, $rfqId);

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $validated = $request->validate([
            'categories' => ['sometimes', 'array', 'min:1', 'max:20'],
            'categories.*' => ['required', 'string', 'max:128'],
            'description' => ['sometimes', 'string', 'max:2000'],
            'geography' => ['nullable', 'string', 'max:64'],
            'spend_band' => ['nullable', 'string', 'max:64'],
            'line_item_summary' => ['sometimes', 'array', 'max:100'],
            'line_item_summary.*' => ['required', 'string', 'max:500'],
            'candidate_limit' => ['sometimes', 'integer', 'min:1', 'max:500'],
        ]);
        $candidateLimit = (int) ($validated['candidate_limit'] ?? 100);

        $vendors = Vendor::query()
            ->whereRaw('lower(tenant_id) = ?', [$this->normalizeIdentifier($tenantId)])
            ->orderByRaw("CASE WHEN lower(status) = 'approved' THEN 0 ELSE 1 END")
            ->orderBy('display_name')
            ->orderBy('id')
            ->limit($candidateLimit)
            ->get();

        $recommendationRequest = new VendorRecommendationRequest(
            tenantId: $this->normalizeIdentifier($tenantId),
            rfqId: (string) $rfq->id,
            categories: $this->stringList($validated['categories'] ?? [$rfq->category]),
            description: trim((string) ($validated['description'] ?? $rfq->description ?? $rfq->title ?? '')),
            geography: $this->nullableString($validated['geography'] ?? null),
            spendBand: $this->nullableString($validated['spend_band'] ?? $this->spendBand($rfq)),
            lineItemSummary: $this->stringList($validated['line_item_summary'] ?? $this->lineItemSummary($rfq)),
            candidates: $vendors
                ->map(fn (Vendor $vendor): VendorRecommendationCandidate => $this->candidateFromVendor($vendor))
                ->values()
                ->all(),
        );

        $result = $coordinator->recommend($recommendationRequest);
        $candidateStatusById = $vendors->mapWithKeys(
            static fn (Vendor $vendor): array => [(string) $vendor->id => strtolower(trim((string) $vendor->status))],
        )->all();

        return response()->json([
            'data' => [
                'tenant_id' => $result->tenantId,
                'rfq_id' => $result->rfqId,
                'candidates' => array_map(
                    fn (VendorRecommendationScoredCandidate $candidate): array => $this->serializeCandidate($candidate),
                    $result->candidates,
                ),
                'excluded_reasons' => array_map(
                    static fn (array $reason): array => array_merge($reason, [
                        'status' => self::excludedReasonStatus($reason, $candidateStatusById),
                    ]),
                    $result->excludedReasons,
                ),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $reason
     * @param array<string, string> $candidateStatusById
     */
    private static function excludedReasonStatus(array $reason, array $candidateStatusById): string
    {
        if (!array_key_exists('vendor_id', $reason)) {
            return 'no_vendor_id';
        }

        $vendorId = trim((string) $reason['vendor_id']);
        if ($vendorId === '') {
            return 'no_vendor_id';
        }

        return $candidateStatusById[$vendorId] ?? 'unknown_vendor';
    }

    private function findRfq(string $tenantId, string $rfqId): ?Rfq
    {
        return Rfq::query()
            ->whereRaw('lower(tenant_id) = ?', [$this->normalizeIdentifier($tenantId)])
            ->where(static function ($builder) use ($rfqId): void {
                $normalizedRfqId = strtolower(trim($rfqId));
                $builder
                    ->whereRaw('lower(id) = ?', [$normalizedRfqId])
                    ->orWhereRaw('lower(rfq_number) = ?', [$normalizedRfqId]);
            })
            ->first();
    }

    private function candidateFromVendor(Vendor $vendor): VendorRecommendationCandidate
    {
        $metadata = is_array($vendor->metadata) ? $vendor->metadata : [];

        return new VendorRecommendationCandidate(
            vendorId: (string) $vendor->id,
            vendorName: $this->vendorDisplayName($vendor),
            status: strtolower(trim((string) $vendor->status)),
            categories: $this->stringList($metadata['categories'] ?? []),
            capabilities: $this->stringList($metadata['capabilities'] ?? []),
            regions: $this->stringList($metadata['regions'] ?? [$vendor->country_of_registration, $vendor->country_code]),
            spendBand: $this->nullableString($metadata['spend_band'] ?? null),
            lastActiveAt: $this->dateTimeOrNull($metadata['last_active_at'] ?? $vendor->updated_at),
            historicalParticipationCount: max(0, (int) ($metadata['historical_participation_count'] ?? 0)),
            historicalAwardCount: max(0, (int) ($metadata['historical_award_count'] ?? 0)),
            preferred: (bool) ($metadata['preferred'] ?? false),
            metadata: $metadata,
        );
    }

    private function serializeCandidate(VendorRecommendationScoredCandidate $candidate): array
    {
        return [
            'vendor_id' => $candidate->vendorId,
            'vendor_name' => $candidate->vendorName,
            'fit_score' => $candidate->fitScore,
            'confidence_band' => $candidate->confidenceBand,
            'recommended_reason_summary' => $candidate->recommendedReasonSummary,
            'deterministic_reasons' => $candidate->deterministicReasons,
            'llm_insights' => $candidate->llmInsights,
            'warning_flags' => $candidate->warningFlags,
            'warnings' => $candidate->warnings,
        ];
    }

    private function vendorDisplayName(Vendor $vendor): string
    {
        foreach ([$vendor->display_name, $vendor->legal_name, $vendor->trading_name, $vendor->name] as $candidate) {
            $value = trim((string) $candidate);

            if ($value !== '') {
                return $value;
            }
        }

        return (string) $vendor->id;
    }

    /**
     * @return list<string>
     */
    private function lineItemSummary(Rfq $rfq): array
    {
        return RfqLineItem::query()
            ->whereRaw('lower(tenant_id) = ?', [$this->normalizeIdentifier((string) $rfq->tenant_id)])
            ->where('rfq_id', $rfq->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(static function (RfqLineItem $lineItem): string {
                return trim(implode(' ', array_filter([
                    (string) $lineItem->description,
                    (string) $lineItem->quantity,
                    (string) $lineItem->uom,
                ], static fn (string $value): bool => trim($value) !== '')));
            })
            ->filter(static fn (string $value): bool => $value !== '')
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $items[] = $item;
            }
        }

        return array_values(array_unique($items));
    }

    private function dateTimeOrNull(mixed $value): ?\DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value)->setTimezone(new DateTimeZone('UTC'));
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'));
        } catch (\Exception) {
            return null;
        }
    }

    private function spendBand(Rfq $rfq): ?string
    {
        $estimatedValue = $rfq->estimated_value !== null ? (float) $rfq->estimated_value : null;
        if ($estimatedValue === null || $estimatedValue <= 0.0) {
            return null;
        }

        return match (true) {
            $estimatedValue < 10000.0 => 'low',
            $estimatedValue < 100000.0 => 'medium',
            default => 'high',
        };
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeIdentifier(string $value): string
    {
        return strtolower(trim($value));
    }
}
