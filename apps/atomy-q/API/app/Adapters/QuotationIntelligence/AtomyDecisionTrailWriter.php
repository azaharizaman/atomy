<?php

declare(strict_types=1);

namespace App\Adapters\QuotationIntelligence;

use App\Models\DecisionTrailEntry;
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
        array $entries,
        int $startingSequence = 1,
        string $previousHash = ''
    ): array {
        $results = [];
        $currentPreviousHash = $previousHash;
        $sequence = $startingSequence;

        foreach ($entries as $entry) {
            $payload = $entry['payload'];
            $payloadHash = hash('sha256', (string) json_encode($payload));
            
            // Simplified hashing logic for the bridge/mock
            $entryHash = hash('sha256', $currentPreviousHash . $payloadHash . $entry['event_type']);

            // Note: comparison_run_id is required in schema, but for quote ingestion, 
            // we might not have it yet. We'll use a placeholder if it's not provided 
            // from some higher-level context.
            DecisionTrailEntry::create([
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqId,
                'comparison_run_id' => $payload['comparison_run_id'] ?? '00000000000000000000000000', // Placeholder
                'sequence' => $sequence,
                'event_type' => $entry['event_type'],
                'payload_hash' => $payloadHash,
                'previous_hash' => $currentPreviousHash,
                'entry_hash' => $entryHash,
                'occurred_at' => now(),
            ]);

            $results[] = [
                'sequence' => $sequence,
                'event_type' => $entry['event_type'],
                'payload_hash' => $payloadHash,
                'previous_hash' => $currentPreviousHash,
                'entry_hash' => $entryHash,
                'occurred_at' => now()->toIso8601String(),
            ];

            $currentPreviousHash = $entryHash;
            $sequence++;
        }

        return $results;
    }
}
