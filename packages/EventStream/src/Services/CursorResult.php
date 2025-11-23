<?php

declare(strict_types=1);

namespace Nexus\EventStream\Services;

use Nexus\EventStream\Contracts\CursorResultInterface;
use Nexus\EventStream\Contracts\EventInterface;

/**
 * Cursor Result
 *
 * Immutable value object representing a page of query results with cursor pagination.
 *
 * @package Nexus\EventStream\Services
 */
final readonly class CursorResult implements CursorResultInterface
{
    /**
     * @param EventInterface[] $events Events in this result set
     * @param string|null $nextCursor HMAC-signed cursor for next page
     * @param string|null $currentCursor Cursor used for this query
     * @param bool $hasMore Whether more results exist
     */
    public function __construct(
        private array $events,
        private ?string $nextCursor,
        private ?string $currentCursor,
        private bool $hasMore
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * {@inheritDoc}
     */
    public function getNextCursor(): ?string
    {
        return $this->nextCursor;
    }

    /**
     * {@inheritDoc}
     */
    public function hasMore(): bool
    {
        return $this->hasMore;
    }

    /**
     * {@inheritDoc}
     */
    public function getCount(): int
    {
        return count($this->events);
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentCursor(): ?string
    {
        return $this->currentCursor;
    }
}
