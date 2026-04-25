<?php

declare(strict_types=1);

namespace App\Services\QuoteIntake;

use App\Models\NormalizationSourceLine;
use App\Models\QuoteSubmission;
use App\Models\RfqLineItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class NormalizationOverrideService
{
    public function __construct(
        private QuoteSubmissionReadinessService $readiness,
        private DecisionTrailRecorder $decisionTrail,
    ) {}

    /**
     * @param array{
     *     override_data: array<string, mixed>,
     *     reason_code: string,
     *     note?: string|null
     * } $validated
     * @return array{line: NormalizationSourceLine, readiness: array{has_blocking_issues: bool, blocking_issue_count: int, next_status: string}}
     */
    public function applyOverride(NormalizationSourceLine $sourceLine, string $actorUserId, array $validated): array
    {
        /** @var array{line: NormalizationSourceLine, readiness: array{has_blocking_issues: bool, blocking_issue_count: int, next_status: string}} $result */
        $result = DB::transaction(function () use ($sourceLine, $actorUserId, $validated): array {
            /** @var NormalizationSourceLine $line */
            $line = NormalizationSourceLine::query()
                ->where('tenant_id', $sourceLine->tenant_id)
                ->where('id', $sourceLine->id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var QuoteSubmission $submission */
            $submission = QuoteSubmission::query()
                ->where('tenant_id', $line->tenant_id)
                ->where('id', $line->quote_submission_id)
                ->lockForUpdate()
                ->firstOrFail();

            $rawData = $line->getRawData();
            $providerProvenance = $this->providerProvenanceSnapshot($line, $rawData);
            $overrideData = $this->normalizeOverrideData($validated['override_data'], $line, $submission);
            $before = $this->beforeValues($line, array_keys($overrideData));

            $this->applyOverrideData($line, $overrideData);

            $after = $this->afterValues($line, array_keys($overrideData));
            $providerConfidence = $line->ai_confidence !== null ? number_format((float) $line->ai_confidence, 2, '.', '') : null;
            $timestamp = Carbon::now()->toAtomString();

            $latestOverride = [
                'actor_user_id' => $actorUserId,
                'timestamp' => $timestamp,
                'reason_code' => $validated['reason_code'],
                'note' => $this->normalizedNote($validated['note'] ?? null),
                'provider_confidence' => $providerConfidence,
                'before' => $before,
                'after' => $after,
                'provider_suggested' => $providerProvenance['suggested_values'] ?? null,
            ];

            $history = is_array($rawData['override_history'] ?? null) ? $rawData['override_history'] : [];
            $history[] = $latestOverride;

            $rawData['provider_provenance'] = $providerProvenance;
            $rawData['override'] = $overrideData;
            $rawData['override_audit'] = $latestOverride;
            $rawData['override_history'] = $history;

            $line->raw_data = $rawData;
            $line->save();

            $readiness = $this->readiness->evaluate($submission);
            $submission->status = $readiness['next_status'];
            $submission->errors_count = $readiness['blocking_issue_count'];
            $submission->save();

            $this->decisionTrail->recordManualSourceLineEvent(
                tenantId: (string) $submission->tenant_id,
                rfqId: (string) $submission->rfq_id,
                quoteSubmissionId: (string) $submission->id,
                sourceLineId: (string) $line->id,
                eventType: 'normalization_source_line_overridden',
                summary: [
                    'origin' => 'buyer_override',
                    'quote_submission_id' => (string) $submission->id,
                    'source_line_id' => (string) $line->id,
                    'actor_user_id' => $actorUserId,
                    'reason_code' => $validated['reason_code'],
                    'note' => $latestOverride['note'],
                    'provider_confidence' => $providerConfidence,
                    'before' => $before,
                    'after' => $after,
                    'provider_suggested' => $providerProvenance['suggested_values'] ?? null,
                    'timestamp' => $timestamp,
                ],
            );

            $line->loadMissing([
                'quoteSubmission:id,tenant_id,rfq_id,vendor_id,vendor_name,status,confidence',
                'rfqLineItem:id,rfq_id,description,quantity,uom,unit_price,currency',
                'conflicts',
            ]);

            return [
                'line' => $line,
                'readiness' => $readiness,
            ];
        });

        return $result;
    }

    /**
     * @param array<string, mixed> $rawData
     * @return array<string, mixed>
     */
    private function providerProvenanceSnapshot(NormalizationSourceLine $line, array $rawData): array
    {
        $existing = $rawData['provider_provenance'] ?? null;
        if (is_array($existing)) {
            if (! is_array($existing['suggested_values'] ?? null)) {
                $existing['suggested_values'] = $line->effectiveValues();
            }

            return $existing;
        }

        return [
            'origin' => 'provider',
            'captured_at' => Carbon::now()->toAtomString(),
            'suggested_values' => $line->effectiveValues(),
            'ai_confidence' => $line->ai_confidence !== null ? number_format((float) $line->ai_confidence, 2, '.', '') : null,
            'taxonomy_code' => $line->taxonomy_code,
            'mapping_version' => $line->mapping_version,
        ];
    }

    /**
     * @param array<string, mixed> $overrideData
     * @return array<string, mixed>
     */
    private function normalizeOverrideData(array $overrideData, NormalizationSourceLine $line, QuoteSubmission $submission): array
    {
        $normalized = [];

        if (array_key_exists('rfq_line_item_id', $overrideData)) {
            $normalized['rfq_line_item_id'] = $this->validatedRfqLineId($submission, $line, $overrideData['rfq_line_item_id']);
        }

        if (array_key_exists('quantity', $overrideData)) {
            $normalized['quantity'] = $this->decimalString($overrideData['quantity'], 4);
        }

        if (array_key_exists('uom', $overrideData)) {
            $normalized['uom'] = trim((string) $overrideData['uom']);
        }

        if (array_key_exists('unit_price', $overrideData)) {
            $normalized['unit_price'] = $this->decimalString($overrideData['unit_price'], 2);
        }

        return $normalized;
    }

    /**
     * @param list<string> $keys
     * @return array<string, string|null>
     */
    private function beforeValues(NormalizationSourceLine $line, array $keys): array
    {
        $effectiveValues = $line->effectiveValues();
        $before = [];

        foreach ($keys as $key) {
            $before[$key] = $effectiveValues[$this->effectiveValueKey($key)] ?? null;
        }

        return $before;
    }

    /**
     * @param list<string> $keys
     * @return array<string, string|null>
     */
    private function afterValues(NormalizationSourceLine $line, array $keys): array
    {
        $effectiveValues = $line->effectiveValues();
        $after = [];

        foreach ($keys as $key) {
            $after[$key] = $effectiveValues[$this->effectiveValueKey($key)] ?? null;
        }

        return $after;
    }

    /**
     * @param array<string, mixed> $overrideData
     */
    private function applyOverrideData(NormalizationSourceLine $line, array $overrideData): void
    {
        if (array_key_exists('rfq_line_item_id', $overrideData)) {
            $line->rfq_line_item_id = $overrideData['rfq_line_item_id'];
        }

        if (array_key_exists('quantity', $overrideData)) {
            $line->source_quantity = $overrideData['quantity'];
        }

        if (array_key_exists('uom', $overrideData)) {
            $line->source_uom = $overrideData['uom'];
        }

        if (array_key_exists('unit_price', $overrideData)) {
            $line->source_unit_price = $overrideData['unit_price'];
        }
    }

    private function validatedRfqLineId(QuoteSubmission $submission, NormalizationSourceLine $line, mixed $rfqLineItemId): string
    {
        $candidate = trim((string) $rfqLineItemId);
        if ($candidate === '') {
            throw ValidationException::withMessages([
                'override_data.rfq_line_item_id' => ['RFQ line id cannot be blank.'],
            ]);
        }

        $rfqLine = RfqLineItem::query()
            ->where('tenant_id', $line->tenant_id)
            ->where('rfq_id', $submission->rfq_id)
            ->where('id', $candidate)
            ->first();

        if ($rfqLine === null) {
            throw ValidationException::withMessages([
                'override_data.rfq_line_item_id' => ['Unknown RFQ line id for this submission.'],
            ]);
        }

        return $candidate;
    }

    private function decimalString(mixed $value, int $scale): string
    {
        return number_format((float) $value, $scale, '.', '');
    }

    private function normalizedNote(mixed $note): ?string
    {
        $normalized = trim((string) $note);

        return $normalized === '' ? null : $normalized;
    }

    private function effectiveValueKey(string $overrideKey): string
    {
        return match ($overrideKey) {
            'quantity' => 'quantity',
            'uom' => 'uom',
            'unit_price' => 'unit_price',
            'rfq_line_item_id' => 'rfq_line_item_id',
            default => $overrideKey,
        };
    }
}
