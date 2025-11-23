<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

/**
 * Cursor Result Interface
 *
 * Framework-agnostic contract for query results with cursor-based pagination.
 * Provides events, pagination metadata, and HMAC-signed cursor for next page.
 *
 * Example Usage:
 * ```php
 * $result = $query->execute();
 *
 * foreach ($result->getEvents() as $event) {
 *     // Process event
 * }
 *
 * if ($result->hasMore()) {
 *     $nextCursor = $result->getNextCursor();
 *     // Use $nextCursor to fetch next page
 * }
 * ```
 *
 * Requirements satisfied:
 * - PER-EVS-7312: Cursor pagination metadata
 * - SEC-EVS-7512: HMAC-signed cursors
 *
 * @package Nexus\EventStream\Contracts
 */
interface CursorResultInterface
{
    /**
     * Get the events in this result set.
     *
     * @return EventInterface[] Array of events
     */
    public function getEvents(): array;

    /**
     * Get the HMAC-signed cursor for the next page.
     *
     * @return string|null Cursor string, or null if no more results
     */
    public function getNextCursor(): ?string;

    /**
     * Check if more results exist beyond this page.
     *
     * @return bool True if more results available
     */
    public function hasMore(): bool;

    /**
     * Get the total count of events in this result set.
     *
     * @return int Number of events returned
     */
    public function getCount(): int;

    /**
     * Get the cursor that was used for this query.
     *
     * @return string|null The cursor used, or null if first page
     */
    public function getCurrentCursor(): ?string;
}
