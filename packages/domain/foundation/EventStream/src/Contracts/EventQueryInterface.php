<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

/**
 * Event Query Interface
 *
 * Framework-agnostic contract for building complex event queries with filtering,
 * ordering, and cursor-based pagination.
 *
 * Pattern:
 * - Fluent Interface: Chain multiple filters and conditions
 * - Cursor Pagination: HMAC-signed cursors for secure, stateless pagination
 * - Immutable Queries: Each method returns a new query instance
 *
 * Example Usage:
 * ```php
 * $query = $queryBuilder
 *     ->where('aggregate_type', '=', 'Account')
 *     ->whereIn('event_type', ['AccountCreated', 'AccountDebited'])
 *     ->orderBy('created_at', 'DESC')
 *     ->withCursor($cursor, 20);
 *
 * $result = $query->execute();
 * // $result->getEvents() - array of events
 * // $result->getNextCursor() - cursor for next page
 * // $result->hasMore() - whether more results exist
 * ```
 *
 * Requirements satisfied:
 * - FUN-EVS-7211: Complex event filtering and querying
 * - PER-EVS-7312: Cursor-based pagination for large result sets
 * - SEC-EVS-7512: HMAC-signed cursors prevent tampering
 *
 * @package Nexus\EventStream\Contracts
 */
interface EventQueryInterface
{
    /**
     * Add a WHERE condition to the query.
     *
     * @param string $field The field name
     * @param string $operator Comparison operator (=, !=, >, <, >=, <=, LIKE)
     * @param mixed $value The value to compare against
     * @return self New query instance with condition added
     */
    public function where(string $field, string $operator, mixed $value): self;

    /**
     * Add a WHERE IN condition to the query.
     *
     * @param string $field The field name
     * @param array<mixed> $values Array of values
     * @return self New query instance with condition added
     */
    public function whereIn(string $field, array $values): self;

    /**
     * Add an ORDER BY clause to the query.
     *
     * @param string $field The field to order by
     * @param string $direction Sort direction (ASC or DESC)
     * @return self New query instance with ordering added
     */
    public function orderBy(string $field, string $direction = 'ASC'): self;

    /**
     * Apply cursor-based pagination to the query.
     *
     * @param string|null $cursor HMAC-signed cursor from previous page, or null for first page
     * @param int $limit Maximum number of results per page
     * @return self New query instance with pagination applied
     */
    public function withCursor(?string $cursor, int $limit): self;

    /**
     * Execute the query and return results.
     *
     * @return CursorResultInterface Query results with pagination metadata
     * @throws \Nexus\EventStream\Exceptions\InvalidCursorException If cursor is invalid or tampered
     */
    public function execute(): CursorResultInterface;

    /**
     * Get the total count of results (without pagination).
     *
     * @return int Total number of events matching the query
     */
    public function count(): int;
}
