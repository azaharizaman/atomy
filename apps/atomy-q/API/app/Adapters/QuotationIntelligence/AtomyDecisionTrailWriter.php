<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence;

use App\Exceptions\DecisionTrailSerializationException;
use App\Models\ComparisonRun;
use App\Models\DecisionTrailEntry;
use App\Models\QuoteSubmission;
use Illuminate\Support\Facades\DB;
use Nexus\QuotationIntelligence\Contracts\DecisionTrailWriterInterface;
use JsonException;
use Psr\Log\LoggerInterface;

final readonly class AtomyDecisionTrailWriter implements DecisionTrailWriterInterface
{
    public function __construct(
        private ?LoggerInterface $logger = null,
    ) {}
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
        array $entries,
        int $startingSequence = 1,
        string $previousHash = ''
    ): array {
        if ($entries === []) {
            return [];
        }

        return DB::transaction(function () use ($tenantId, $rfqId, $entries, $startingSequence, $previousHash) {
            [$resolvedRfqId, $idempotencyKey] = $this->resolveRfqContext($tenantId, $rfqId);

            $results = [];
            $comparisonRunId = $this->resolveComparisonRunId($tenantId, $resolvedRfqId, $idempotencyKey);
            $lastEntry = DecisionTrailEntry::query()
                ->where('tenant_id', $tenantId)
                ->where('comparison_run_id', $comparisonRunId)
                ->orderByDesc('sequence')
                ->lockForUpdate()
                ->first();

            $currentPreviousHash = $previousHash;
            if ($currentPreviousHash === '') {
                $currentPreviousHash = $lastEntry?->entry_hash ?? str_repeat('0', 64);
            }

            $nextSequence = max(
                $startingSequence,
                ((int) ($lastEntry?->sequence ?? 0)) + 1
            );

            $occurredAt = now();

            foreach ($entries as $index => $entry) {
                if (!is_array($entry) || !isset($entry['event_type'], $entry['payload']) || !is_array($entry['payload'])) {
                    $this->logger?->warning('Skipping malformed decision trail entry', [
                        'index' => $index,
                        'entry' => $entry,
                    ]);
                    continue;
                }

                $payload = $entry['payload'];
                try {
                    $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new DecisionTrailSerializationException(
                        'Failed to serialize decision trail payload.',
                        0,
                        $exception
                    );
                }

                $payloadHash = hash('sha256', (string) $payloadJson);
                $entryHash = hash('sha256', $currentPreviousHash . $payloadHash . $entry['event_type']);

                DecisionTrailEntry::query()->create([
                    'tenant_id' => $tenantId,
                    'rfq_id' => $resolvedRfqId,
                    'comparison_run_id' => $comparisonRunId,
                    'sequence' => $nextSequence,
                    'event_type' => $entry['event_type'],
                    'payload_hash' => $payloadHash,
                    'previous_hash' => $currentPreviousHash,
                    'entry_hash' => $entryHash,
                    'occurred_at' => $occurredAt,
                ]);

                $results[] = [
                    'sequence' => $nextSequence,
                    'event_type' => $entry['event_type'],
                    'payload_hash' => $payloadHash,
                    'previous_hash' => $currentPreviousHash,
                    'entry_hash' => $entryHash,
                    'occurred_at' => $occurredAt->toIso8601String(),
                ];

                $currentPreviousHash = $entryHash;
                $nextSequence++;
            }

            return $results;
        });
    }

    /**
     * Legacy entrypoint for backward compatibility with Alpha callers that pass (source, action, payload).
     *
     * @deprecated Use write() with typed array entries instead
     *
     * @param string $tenantId
     * @param string $rfqId
     * @param string $source Event source name
     * @param string $action Action name
     * @param array<string, mixed> $payload Event payload
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
    public function writeLegacy(
        string $tenantId,
        string $rfqId,
        string $source,
        string $action,
        array $payload
    ): array {
        $entries = [[
            'event_type' => $source . ':' . $action,
            'payload' => $payload,
        ]];

        return $this->write($tenantId, $rfqId, $entries, 1, '');
    }

    /**
     * @return array{0: string, 1: string|null} [rfqId, idempotencyKey]
     */
    private function resolveRfqContext(string $tenantId, string $rfqId): array
    {
        $submission = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $rfqId)
            ->lockForUpdate()
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
        $comparisonRunQuery = ComparisonRun::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId);

        if ($idempotencyKey === null) {
            $comparisonRunQuery->whereNull('idempotency_key');
        } else {
            $comparisonRunQuery->where('idempotency_key', $idempotencyKey);
        }

        $comparisonRun = $comparisonRunQuery
            ->orderByDesc('created_at')
            ->lockForUpdate()
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
