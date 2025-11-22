<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Exceptions;

/**
 * Thrown when a metric tag exceeds the configured cardinality limit.
 * 
 * This prevents unbounded tag values (like user IDs, UUIDs) from causing
 * memory exhaustion in the TSDB by tracking and enforcing cardinality thresholds.
 */
final class CardinalityLimitExceededException extends MonitoringException
{
    public function __construct(
        public readonly string $tagKey,
        public readonly int $currentCardinality,
        public readonly ?int $limit = null
    ) {
        $message = sprintf(
            'Cardinality limit exceeded for tag "%s": %d unique values%s',
            $tagKey,
            $currentCardinality,
            $limit !== null ? " (limit: {$limit})" : ''
        );
        
        parent::__construct(
            message: $message,
            context: [
                'tag_key' => $tagKey,
                'current_cardinality' => $currentCardinality,
                'limit' => $limit,
            ]
        );
    }
    
    /**
     * Returns 429 Too Many Requests for API responses.
     */
    public function getHttpStatus(): int
    {
        return 429;
    }
}
