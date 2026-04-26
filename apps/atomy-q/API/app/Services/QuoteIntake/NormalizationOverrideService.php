<?php

declare(strict_types=1);

namespace App\Services\QuoteIntake;

use App\Models\NormalizationSourceLine;
use App\Models\QuoteSubmission;
use App\Models\RfqLineItem;
use App\Models\User;
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
     * @param array{override_data: array<string, mixed>, reason_code: string, note?: string|null} $validated
     * @param string $actorUserId
     * @return array{line: NormalizationSourceLine, readiness: array{has_blocking_issues: bool, blocking_issue_count: int, next_status: string}}
     */
    public function createSourceLine(QuoteSubmission $submission, string $actorUserId, array $validated): array
    {
        return DB::transaction(function () use ($submission, $actorUserId, $validated): array {
            $sourceLine = new NormalizationSourceLine();
            $sourceLine->tenant_id = $submission->tenant_id;
            $sourceLine->quote_submission_id = $submission->id;
            // ... (rest of logic: apply data, save, refresh readiness, record trail)
            return ['line' => $sourceLine, 'readiness' => []];
        });
    }

    /**
     * @param array<string, mixed> $validated
     * @param string $actorUserId
     * @return array{line: NormalizationSourceLine, readiness: array{has_blocking_issues: bool, blocking_issue_count: int, next_status: string}}
     */
    public function updateSourceLine(NormalizationSourceLine $sourceLine, string $actorUserId, array $validated): array
    {
        return DB::transaction(function () use ($sourceLine, $actorUserId, $validated): array {
            $submission = $sourceLine->quoteSubmission;
            $line = $sourceLine;
            $rawData = $line->getRawData();
            $providerProvenance = $this->getProviderProvenanceFromRaw($rawData);
            $overrideData = $this->normalizeOverrideData($validated['override_data'], $line, $submission);
            $before = $this->effectiveValuesFor($line, array_keys($overrideData));

            $this->applyOverrideData($line, $overrideData);

            $after = $this->effectiveValuesFor($line, array_keys($overrideData));
            $providerConfidence = $line->ai_confidence !== null ? $this->decimalStringOrNull($line->ai_confidence, 2) : null;
            $providerSuggestedValues = is_array($providerProvenance) ? ($providerProvenance['suggested_values'] ?? null) : null;
            $timestamp = Carbon::now()->toAtomString();

            $latestOverride = [
                'actor_user_id' => $actorUserId,
                'actor_name' => $this->actorDisplayName((string) $submission->tenant_id, $actorUserId),
                'timestamp' => $timestamp,
                'reason_code' => $validated['reason_code'],
                'note' => $this->normalizedNote($validated['note'] ?? null),
                'provider_confidence' => $providerConfidence,
                'before' => $before,
                'after' => $after,
                'provider_suggested' => $providerSuggestedValues,
            ];

            $history = is_array($rawData['override_history'] ?? null) ? $rawData['override_history'] : [];
            $history[] = $latestOverride;

            if ($providerProvenance !== null) {
                $rawData['provider_provenance'] = $providerProvenance;
            } else {
                unset($rawData['provider_provenance']);
            }
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
                    'actor_name' => $latestOverride['actor_name'],
                    'reason_code' => $validated['reason_code'],
                    'note' => $latestOverride['note'],
                    'provider_confidence' => $providerConfidence,
                    'before' => $before,
                    'after' => $after,
                    'provider_suggested' => $providerSuggestedValues,
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
    }

    /**
     * @return array{line: NormalizationSourceLine, readiness: array{has_blocking_issues: bool, blocking_issue_count: int, next_status: string}}
     */
    public function revertOverride(NormalizationSourceLine $sourceLine, string $actorUserId): array
    {
        return DB::transaction(function () use ($sourceLine, $actorUserId): array {
            $submission = $sourceLine->quoteSubmission;
            $line = $sourceLine;
            $rawData = $line->getRawData();
            $providerProvenance = $this->getProviderProvenanceFromRaw($rawData);
            $previousOverrideAudit = is_array($rawData['override_audit'] ?? null) ? $rawData['override_audit'] : null;

            unset($rawData['override'], $rawData['override_audit'], $rawData['override_history']);
            if ($providerProvenance !== null) {
                $rawData['provider_provenance'] = $providerProvenance;
            } else {
                unset($rawData['provider_provenance']);
            }

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
                eventType: 'normalization_source_line_override_reverted',
                summary: array_filter([
                    'origin' => 'buyer_override',
                    'quote_submission_id' => (string) $submission->id,
                    'source_line_id' => (string) $line->id,
                    'actor_user_id' => $actorUserId,
                    'previous_reason_code' => is_array($previousOverrideAudit) ? ($previousOverrideAudit['reason_code'] ?? null) : null,
                    'timestamp' => Carbon::now()->toAtomString(),
                ], static fn (mixed $value): bool => $value !== null),
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
    }

    /**
     * @param array<string, mixed> $rawData
     * @return array<string, mixed>|null
     */
    private function getProviderProvenanceFromRaw(array $rawData): ?array
    {
        $existing = $rawData['provider_provenance'] ?? null;
        if (is_array($existing)) {
            return $existing;
        }

        return null;
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

        if (array_key_exists('source_description', $overrideData)) {
            $normalized['source_description'] = trim((string) $overrideData['source_description']);
        }

        if (array_key_exists('quantity', $overrideData)) {
            $normalized['quantity'] = $this->decimalStringOrNull($overrideData['quantity'], 4);
        }

        if (array_key_exists('uom', $overrideData)) {
            $normalized['uom'] = $this->nullableString($overrideData['uom']);
        }

        if (array_key_exists('unit_price', $overrideData)) {
            $normalized['unit_price'] = $this->decimalStringOrNull($overrideData['unit_price'], 4);
        }

        return $normalized;
    }

    /**
     * @param list<string> $keys
     * @return array<string, string|null>
     */
    private function effectiveValuesFor(NormalizationSourceLine $line, array $keys): array
    {
        $effectiveValues = $line->effectiveValues();
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $effectiveValues[$key] ?? null;
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $overrideData
     */
    private function applyOverrideData(NormalizationSourceLine $line, array $overrideData): void
    {
        if (array_key_exists('rfq_line_item_id', $overrideData)) {
            $line->rfq_line_item_id = $overrideData['rfq_line_item_id'];
        }

        if (array_key_exists('source_description', $overrideData)) {
            $line->source_description = $overrideData['source_description'];
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

    private function validatedRfqLineId(QuoteSubmission $submission, NormalizationSourceLine $line, mixed $rfqLineItemId): ?string
    {
        $candidate = trim((string) $rfqLineItemId);
        if ($candidate === '') {
            return null;
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

    private function decimalStringOrNull(mixed $value, int $scale): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return $this->nullableString($value);
        }

        $normalized = (string) $value;

        if (function_exists('bcadd')) {
            return bcadd($normalized, '0', $scale);
        }

        return $this->normalizeDecimalString($normalized, $scale);
    }

    private function normalizeDecimalString(string $value, int $scale): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (! preg_match('/^([+-]?)(\d+)(?:\.(\d+))?$/', $trimmed, $matches)) {
            return $this->nullableString($trimmed);
        }

        $sign = $matches[1];
        $integerPart = ltrim($matches[2], '0');
        if ($integerPart === '') {
            $integerPart = '0';
        }

        $fractionPart = $matches[3] ?? '';
        if ($scale === 0) {
            return $sign . $integerPart;
        }

        $fractionPart = substr($fractionPart, 0, $scale);
        $fractionPart = str_pad($fractionPart, $scale, '0');

        return $sign . $integerPart . '.' . $fractionPart;
    }

    private function normalizedNote(mixed $note): ?string
    {
        $normalized = trim((string) $note);

        return $normalized === '' ? null : $normalized;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function actorDisplayName(string $tenantId, string $actorUserId): string
    {
        $name = User::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $actorUserId)
            ->value('name');

        $normalized = is_string($name) ? trim($name) : '';

        return $normalized !== '' ? $normalized : $actorUserId;
    }
}
