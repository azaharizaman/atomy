<?php

declare(strict_types=1);

namespace Nexus\EventStream\Exceptions;

/**
 * Invalid Cursor Exception
 *
 * Thrown when a cursor fails validation (malformed, HMAC mismatch, expired).
 *
 * @package Nexus\EventStream\Exceptions
 */
final class InvalidCursorException extends \RuntimeException
{
    private function __construct(
        string $message,
        private readonly string $cursor,
        private readonly string $reason
    ) {
        parent::__construct($message);
    }

    /**
     * Create exception for malformed cursor (invalid base64 or JSON).
     *
     * @param string $cursor The malformed cursor
     * @return self
     */
    public static function malformed(string $cursor): self
    {
        return new self(
            "Cursor is malformed and cannot be decoded",
            $cursor,
            'malformed'
        );
    }

    /**
     * Create exception for HMAC signature mismatch.
     *
     * @param string $cursor The cursor with invalid signature
     * @return self
     */
    public static function invalidSignature(string $cursor): self
    {
        return new self(
            "Cursor signature verification failed (possible tampering)",
            $cursor,
            'invalid_signature'
        );
    }

    /**
     * Create exception for expired cursor.
     *
     * @param string $cursor The expired cursor
     * @param \DateTimeImmutable $expiredAt When cursor expired
     * @return self
     */
    public static function expired(string $cursor, \DateTimeImmutable $expiredAt): self
    {
        return new self(
            sprintf(
                "Cursor expired at %s",
                $expiredAt->format('Y-m-d H:i:s')
            ),
            $cursor,
            'expired'
        );
    }

    /**
     * Create exception for missing required fields in cursor.
     *
     * @param string $cursor The invalid cursor
     * @param string[] $missingFields Required fields that are missing
     * @return self
     */
    public static function missingFields(string $cursor, array $missingFields): self
    {
        return new self(
            sprintf(
                "Cursor missing required fields: %s",
                implode(', ', $missingFields)
            ),
            $cursor,
            'missing_fields'
        );
    }

    /**
     * Get the invalid cursor string.
     *
     * @return string
     */
    public function getCursor(): string
    {
        return $this->cursor;
    }

    /**
     * Get the reason for cursor invalidity.
     *
     * @return string One of: malformed, invalid_signature, expired, missing_fields
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
