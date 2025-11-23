<?php

declare(strict_types=1);

namespace Nexus\EventStream\Services;

use Nexus\EventStream\Contracts\CursorPaginatorInterface;
use Nexus\EventStream\Contracts\CursorResultInterface;
use Nexus\EventStream\Contracts\EventInterface;
use Nexus\EventStream\Contracts\EventQueryInterface;
use Nexus\EventStream\Contracts\EventStoreInterface;

/**
 * Stream Query Engine
 *
 * Fluent query builder for event streams with cursor-based pagination.
 * Provides filtering, ordering, and secure pagination capabilities.
 *
 * IMMUTABILITY: All query methods return new instances (Immutable Query Pattern).
 *
 * Example Usage:
 * ```php
 * $result = $queryEngine
 *     ->where('aggregate_id', '01HXZ...')
 *     ->where('event_type', 'AccountCredited')
 *     ->orderBy('occurred_at', 'asc')
 *     ->limit(50)
 *     ->execute();
 *
 * foreach ($result->getEvents() as $event) {
 *     // Process event
 * }
 *
 * if ($result->hasMore()) {
 *     $nextPage = $queryEngine
 *         ->where('aggregate_id', '01HXZ...')
 *         ->withCursor($result->getNextCursor())
 *         ->execute();
 * }
 * ```
 *
 * Requirements satisfied:
 * - FUN-EVS-7211: Complex event querying with filters
 * - PER-EVS-7312: Cursor-based pagination
 * - SEC-EVS-7512: HMAC-signed cursors
 *
 * @package Nexus\EventStream\Services
 */
final readonly class StreamQueryEngine implements EventQueryInterface
{
    /**
     * @param EventStoreInterface $eventStore Event store for querying
     * @param CursorPaginatorInterface $cursorPaginator Cursor generator/validator
     * @param array<string, array{operator: string, value: mixed}> $filters WHERE conditions with operators
     * @param array<string, array<int|string>> $inFilters WHERE IN conditions
     * @param string $orderByField Sort field (default: sequence)
     * @param string $orderDirection Sort direction (asc/desc)
     * @param int $limit Maximum results per page
     * @param string|null $cursor Current pagination cursor
     */
    public function __construct(
        private EventStoreInterface $eventStore,
        private CursorPaginatorInterface $cursorPaginator,
        private array $filters = [],
        private array $inFilters = [],
        private string $orderByField = 'sequence',
        private string $orderDirection = 'asc',
        private int $limit = 100,
        private ?string $cursor = null
    ) {}

    /**
     * {@inheritDoc}
     */
    public function where(string $field, string $operator, mixed $value): self
    {
        $filters = $this->filters;
        $filters[$field] = ['operator' => $operator, 'value' => $value];

        return new self(
            $this->eventStore,
            $this->cursorPaginator,
            $filters,
            $this->inFilters,
            $this->orderByField,
            $this->orderDirection,
            $this->limit,
            $this->cursor
        );
    }

    /**
     * {@inheritDoc}
     */
    public function whereIn(string $field, array $values): self
    {
        $inFilters = $this->inFilters;
        $inFilters[$field] = $values;

        return new self(
            $this->eventStore,
            $this->cursorPaginator,
            $this->filters,
            $inFilters,
            $this->orderByField,
            $this->orderDirection,
            $this->limit,
            $this->cursor
        );
    }

    /**
     * {@inheritDoc}
     */
    public function orderBy(string $field, string $direction = 'asc'): self
    {
        $direction = strtolower($direction);
        if (!in_array($direction, ['asc', 'desc'], true)) {
            throw new \InvalidArgumentException(
                "Order direction must be 'asc' or 'desc', got: {$direction}"
            );
        }

        return new self(
            $this->eventStore,
            $this->cursorPaginator,
            $this->filters,
            $this->inFilters,
            $field,
            $direction,
            $this->limit,
            $this->cursor
        );
    }

    /**
     * {@inheritDoc}
     */
    public function limit(int $limit): self
    {
        if ($limit < 1) {
            throw new \InvalidArgumentException(
                "Limit must be at least 1, got: {$limit}"
            );
        }

        return new self(
            $this->eventStore,
            $this->cursorPaginator,
            $this->filters,
            $this->inFilters,
            $this->orderByField,
            $this->orderDirection,
            $limit,
            $this->cursor
        );
    }

    /**
     * {@inheritDoc}
     */
    public function withCursor(?string $cursor, int $limit): self
    {
        if ($limit < 1) {
            throw new \InvalidArgumentException(
                "Limit must be at least 1, got: {$limit}"
            );
        }

        return new self(
            $this->eventStore,
            $this->cursorPaginator,
            $this->filters,
            $this->inFilters,
            $this->orderByField,
            $this->orderDirection,
            $limit,
            $cursor
        );
    }

    /**
     * {@inheritDoc}
     */
    public function execute(): CursorResultInterface
    {
        // Parse cursor if provided
        $cursorData = null;
        if ($this->cursor !== null) {
            $cursorData = $this->cursorPaginator->parseCursor($this->cursor);
        }

        // Fetch events from store
        // NOTE: Application layer (Atomy) must implement EventStoreInterface::query()
        $events = $this->eventStore->query(
            $this->filters,
            $this->inFilters,
            $this->orderByField,
            $this->orderDirection,
            $this->limit + 1, // Fetch one extra to check if more exist
            $cursorData
        );

        // Determine if more results exist
        $hasMore = count($events) > $this->limit;
        if ($hasMore) {
            array_pop($events); // Remove the extra event
        }

        // Generate next cursor if more results exist
        $nextCursor = null;
        if ($hasMore && !empty($events)) {
            $lastEvent = end($events);
            $nextCursor = $this->cursorPaginator->createCursor(
                $lastEvent->getEventId(),
                $lastEvent->getVersion()
            );
        }

        return new CursorResult(
            $events,
            $nextCursor,
            $this->cursor,
            $hasMore
        );
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        // NOTE: Application layer (Atomy) must implement EventStoreInterface::count()
        return $this->eventStore->count($this->filters, $this->inFilters);
    }
}
