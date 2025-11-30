<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

/**
 * Cursor Paginator Interface
 *
 * Framework-agnostic contract for cursor-based pagination orchestration.
 * Generates HMAC-signed cursors and validates cursor integrity.
 *
 * SECURITY: Uses HMAC-SHA256 to prevent cursor tampering.
 *
 * Example Usage:
 * ```php
 * // Create cursor for next page
 * $cursor = $paginator->createCursor($lastEventId, $lastSequence);
 *
 * // Parse and validate cursor
 * $data = $paginator->parseCursor($cursor);
 * // ['event_id' => '...', 'sequence' => 123]
 * ```
 *
 * Requirements satisfied:
 * - PER-EVS-7312: Cursor generation and parsing
 * - SEC-EVS-7512: HMAC-signed cursors for integrity
 *
 * @package Nexus\EventStream\Contracts
 */
interface CursorPaginatorInterface
{
    /**
     * Create an HMAC-signed cursor from pagination data.
     *
     * @param string $lastEventId The last event ID in current page
     * @param int $lastSequence The last sequence number in current page
     * @param array<string, mixed> $additionalData Optional metadata
     * @return string Base64-encoded, HMAC-signed cursor
     */
    public function createCursor(
        string $lastEventId,
        int $lastSequence,
        array $additionalData = []
    ): string;

    /**
     * Parse and validate an HMAC-signed cursor.
     *
     * @param string $cursor Base64-encoded cursor string
     * @return array{event_id: string, sequence: int} Decoded cursor data
     *
     * @throws \Nexus\EventStream\Exceptions\InvalidCursorException If cursor is malformed or HMAC invalid
     */
    public function parseCursor(string $cursor): array;

    /**
     * Check if a cursor is valid (HMAC verification only).
     *
     * @param string $cursor Base64-encoded cursor string
     * @return bool True if HMAC signature is valid
     */
    public function isValidCursor(string $cursor): bool;
}
