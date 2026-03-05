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
    public function write(string $tenantId, string $rfqId, array $entries, int $startingSequence = 1, string $previousHash = ''): array
    {
        if ($startingSequence < 1) {
            throw new \InvalidArgumentException('startingSequence must be greater than or equal to 1.');
        }

        if ($previousHash !== '' && !preg_match('/^[a-f0-9]{64}$/i', $previousHash)) {
            throw new \InvalidArgumentException('previousHash must be a valid 64-character hex SHA-256 string.');
        }

        $trail = [];
        $currentPreviousHash = $previousHash === '' ? str_repeat('0', 64) : $previousHash;

        foreach ($entries as $index => $entry) {
            $sequence = $startingSequence + $index;
            
            if (!isset($entry['event_type']) || !is_string($entry['event_type']) || trim($entry['event_type']) === '') {
                throw new \InvalidArgumentException(sprintf('Entry at index %d must have a non-empty string "event_type".', $index));
            }
            $eventType = trim($entry['event_type']);

            if (!isset($entry['payload']) || !is_array($entry['payload'])) {
                throw new \InvalidArgumentException(sprintf('Entry at index %d must have an array "payload".', $index));
            }
            $payload = $entry['payload'];
            
            $occurredAt = (new \DateTimeImmutable())->format(DATE_ATOM);

            $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);
            $payloadHash = hash('sha256', $payloadJson);

            $entryHash = hash('sha256', json_encode([
                'tenant_id' => $tenantId,
                'rfq_id' => $rfqId,
                'sequence' => $sequence,
                'event_type' => $eventType,
                'payload_hash' => $payloadHash,
                'previous_hash' => $currentPreviousHash,
                'occurred_at' => $occurredAt,
            ], JSON_THROW_ON_ERROR));

            $trail[] = [
                'sequence' => $sequence,
                'event_type' => $eventType,
                'payload_hash' => $payloadHash,
                'previous_hash' => $currentPreviousHash,
                'entry_hash' => $entryHash,
                'occurred_at' => $occurredAt,
            ];

            $currentPreviousHash = $entryHash;
        }

        return $trail;
    }
}

