<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Services;

use Nexus\QuotationIntelligence\Contracts\DecisionTrailWriterInterface;

/**
 * Produces immutable hash-chained trail entries for governance/audit.
 */
final readonly class HashChainedDecisionTrailWriter implements DecisionTrailWriterInterface
{
    /**
     * @inheritDoc
     */
    public function write(string $tenantId, string $rfqId, array $entries): array
    {
        $trail = [];
        $previousHash = str_repeat('0', 64);

        foreach ($entries as $index => $entry) {
            $sequence = $index + 1;
            $eventType = (string)($entry['event_type'] ?? 'unknown');
            $payload = is_array($entry['payload'] ?? null) ? $entry['payload'] : [];
            $occurredAt = (new \DateTimeImmutable())->format(DATE_ATOM);

            $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);
            $payloadHash = hash('sha256', $payloadJson);

            $entryHash = hash('sha256', json_encode([
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqId,
                'sequence' => $sequence,
                'event_type' => $eventType,
                'payload_hash' => $payloadHash,
                'previous_hash' => $previousHash,
                'occurred_at' => $occurredAt,
            ], JSON_THROW_ON_ERROR));

            $trail[] = [
                'sequence' => $sequence,
                'event_type' => $eventType,
                'payload_hash' => $payloadHash,
                'previous_hash' => $previousHash,
                'entry_hash' => $entryHash,
                'occurred_at' => $occurredAt,
            ];

            $previousHash = $entryHash;
        }

        return $trail;
    }
}

