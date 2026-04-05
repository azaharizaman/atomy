<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence;

use App\Models\ComparisonRun;
use App\Models\DecisionTrailEntry;
use App\Models\QuoteSubmission;
use Nexus\QuotationIntelligence\Contracts\DecisionTrailWriterInterface;

final class AtomyDecisionTrailWriter implements DecisionTrailWriterInterface
{
    /**
     * @param string $tenantId
     * @param string $rfqId
     * @param array<int, array{
     *   event_type: string,
     *   payload: array<string, mixed>
     * }> $entries
     * @param int $startingSequence Starting sequence number (must be >= 1)
     * @param string $previousHash Previous entry hash (64-char hex) or empty for first entry
     *
     * @return array<int, array{
     *   sequence: int,
     *   event_type: string,
     *   payload_hash: string,
     *   previous_hash: string,
     *   entry_hash: string,
     *   occurred_at: string
     * }>
     */
    public function write(
        string $tenantId,
        string $rfqId,
        $entries,
        $startingSequence = 1,
        $previousHash = ''
    ): array {
        // Backward-compat shim: some Alpha callers still pass (source, action, payload)
        // instead of (entries, startingSequence, previousHash).
        if (is_string($entries) && is_string($startingSequence) && is_array($previousHash)) {
            $entries = [[
                'event_type' => $entries . ':' . $startingSequence,
                'payload' => $previousHash,
            ]];
            $startingSequence = 1;
            $previousHash = '';
        }

        if (!is_array($entries)) {
            return [];
        }

        [$resolvedRfqId, $idempotencyKey] = $this->resolveRfqContext($tenantId, $rfqId);

        $results = [];
        $comparisonRunId = $this->resolveComparisonRunId($tenantId, $resolvedRfqId, $idempotencyKey);

        $currentPreviousHash = is_string($previousHash) ? $previousHash : '';
        if ($currentPreviousHash === '') {
            $last = DecisionTrailEntry::query()
                ->where('tenant_id', $tenantId)
                ->where('comparison_run_id', $comparisonRunId)
                ->orderByDesc('sequence')
                ->first();

            $currentPreviousHash = $last?->entry_hash ?? str_repeat('0', 64);
        }

        $nextSequence = max(
            (int) $startingSequence,
            ((int) DecisionTrailEntry::query()
                ->where('tenant_id', $tenantId)
                ->where('comparison_run_id', $comparisonRunId)
                ->max('sequence')) + 1
        );

        foreach ($entries as $entry) {
            if (!is_array($entry) || !isset($entry['event_type'], $entry['payload']) || !is_array($entry['payload'])) {
                continue;
            }

            $payload = $entry['payload'];
            $payloadHash = hash('sha256', (string) json_encode($payload, JSON_THROW_ON_ERROR));
            $entryHash = hash('sha256', $currentPreviousHash . $payloadHash . $entry['event_type']);

            DecisionTrailEntry::create([
                'tenant_id' => $tenantId,
                'rfq_id' => $resolvedRfqId,
                'comparison_run_id' => $comparisonRunId,
                'sequence' => $nextSequence,
                'event_type' => $entry['event_type'],
                'payload_hash' => $payloadHash,
                'previous_hash' => $currentPreviousHash,
                'entry_hash' => $entryHash,
                'occurred_at' => now(),
            ]);

            $results[] = [
                'sequence' => $nextSequence,
                'event_type' => $entry['event_type'],
                'payload_hash' => $payloadHash,
                'previous_hash' => $currentPreviousHash,
                'entry_hash' => $entryHash,
                'occurred_at' => now()->toIso8601String(),
            ];

            $currentPreviousHash = $entryHash;
            $nextSequence++;
        }

        return $results;
    }

    /**
     * @return array{0: string, 1: string|null} [rfqId, idempotencyKey]
     */
    private function resolveRfqContext(string $tenantId, string $rfqId): array
    {
        $submission = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $rfqId)
            ->first();

        if ($submission === null) {
            return [$rfqId, null];
        }

        // If caller accidentally passed QuoteSubmissionId, translate to RFQ context and
        // use an idempotency key to keep a per-submission comparison run.
        return [(string) $submission->rfq_id, 'quote-submission:' . (string) $submission->id];
    }

    private function resolveComparisonRunId(string $tenantId, string $rfqId, ?string $idempotencyKey): string
    {
        $comparisonRun = ComparisonRun::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId)
            ->when($idempotencyKey !== null, static fn ($q) => $q->where('idempotency_key', $idempotencyKey))
            ->orderByDesc('created_at')
            ->first();

        if ($comparisonRun !== null) {
            return $comparisonRun->id;
        }

        /** @var ComparisonRun $comparisonRun */
        $comparisonRun = ComparisonRun::query()->create([
            'tenant_id' => $tenantId,
            'rfq_id' => $rfqId,
            'idempotency_key' => $idempotencyKey,
            'name' => 'AI normalization decision trail',
            'description' => 'Auto-created comparison run for quote ingestion decision trail entries.',
            'is_preview' => false,
            'status' => 'draft',
            'version' => 1,
        ]);

        return $comparisonRun->id;
    }
}
