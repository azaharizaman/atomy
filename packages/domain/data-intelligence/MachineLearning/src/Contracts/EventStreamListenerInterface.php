<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

/**
 * EventStream listener interface
 * 
 * Defines contract for consuming events from Nexus\EventStream package.
 * Application layer implements polling/subscription logic.
 */
interface EventStreamListenerInterface
{
    /**
     * Poll for new events from EventStream
     * 
     * @param string $aggregateType Aggregate type to poll (e.g., 'journal_entry')
     * @param int $fromPosition Starting position cursor
     * @param int $limit Maximum events to retrieve
     * @return array<object> Array of domain events
     */
    public function pollEvents(string $aggregateType, int $fromPosition, int $limit = 100): array;
    
    /**
     * Get last processed event position for a context
     * 
     * @param string $context Intelligence context (e.g., 'finance_anomaly_detection')
     * @param string $aggregateType Aggregate type
     * @return int Last processed position, 0 if never processed
     */
    public function getLastProcessedPosition(string $context, string $aggregateType): int;
    
    /**
     * Mark an event as processed
     * 
     * @param string $context Intelligence context
     * @param string $aggregateType Aggregate type
     * @param string $eventId Event identifier
     * @param int $position Event position in stream
     * @return void
     */
    public function markEventProcessed(string $context, string $aggregateType, string $eventId, int $position): void;
    
    /**
     * Determine optimal polling interval based on system state
     * 
     * @return int Polling interval in seconds
     */
    public function determinePollingInterval(): int;
}
