<?php

declare(strict_types=1);

namespace Nexus\EventStream\Services;

use Nexus\EventStream\Contracts\CursorPaginatorInterface;
use Nexus\EventStream\Exceptions\InvalidCursorException;

/**
 * HMAC Cursor Paginator
 *
 * Generates and validates HMAC-SHA256 signed cursors for secure stateless pagination.
 *
 * SECURITY:
 * - Uses HMAC-SHA256 to prevent cursor tampering
 * - Secret key should be minimum 32 bytes (256-bit)
 * - Cursors are base64-encoded JSON payloads with HMAC signature
 *
 * Cursor Format:
 * ```json
 * {
 *   "event_id": "01HXZ...",
 *   "sequence": 12345,
 *   "additional": {...},
 *   "hmac": "a8b7c6d5..."
 * }
 * ```
 *
 * Requirements satisfied:
 * - PER-EVS-7312: Cursor-based pagination
 * - SEC-EVS-7512: HMAC-signed cursors
 *
 * @package Nexus\EventStream\Services
 */
final readonly class HmacCursorPaginator implements CursorPaginatorInterface
{
    /**
     * @param string $secretKey HMAC secret (minimum 32 bytes recommended)
     */
    public function __construct(
        private string $secretKey
    ) {
        if (strlen($secretKey) < 32) {
            throw new \InvalidArgumentException(
                'HMAC secret key must be at least 32 bytes for security'
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createCursor(
        string $lastEventId,
        int $lastSequence,
        array $additionalData = []
    ): string {
        $payload = [
            'event_id' => $lastEventId,
            'sequence' => $lastSequence,
        ];

        if (!empty($additionalData)) {
            $payload['additional'] = $additionalData;
        }

        // Generate HMAC signature
        $hmac = $this->generateHmac($payload);
        $payload['hmac'] = $hmac;

        // Base64 encode the JSON payload
        return base64_encode(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * {@inheritDoc}
     */
    public function parseCursor(string $cursor): array
    {
        // Decode base64
        $decoded = base64_decode($cursor, true);
        if ($decoded === false) {
            throw InvalidCursorException::malformed($cursor);
        }

        // Parse JSON
        try {
            $payload = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw InvalidCursorException::malformed($cursor);
        }

        // Validate required fields
        $missingFields = [];
        if (!isset($payload['event_id'])) {
            $missingFields[] = 'event_id';
        }
        if (!isset($payload['sequence'])) {
            $missingFields[] = 'sequence';
        }
        if (!isset($payload['hmac'])) {
            $missingFields[] = 'hmac';
        }

        if (!empty($missingFields)) {
            throw InvalidCursorException::missingFields($cursor, $missingFields);
        }

        // Verify HMAC signature
        $providedHmac = $payload['hmac'];
        unset($payload['hmac']);

        $expectedHmac = $this->generateHmac($payload);
        if (!hash_equals($expectedHmac, $providedHmac)) {
            throw InvalidCursorException::invalidSignature($cursor);
        }

        return [
            'event_id' => $payload['event_id'],
            'sequence' => (int) $payload['sequence'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function isValidCursor(string $cursor): bool
    {
        try {
            $this->parseCursor($cursor);
            return true;
        } catch (InvalidCursorException) {
            return false;
        }
    }

    /**
     * Generate HMAC-SHA256 signature for payload.
     *
     * @param array<string, mixed> $payload Data to sign
     * @return string Hexadecimal HMAC signature
     */
    private function generateHmac(array $payload): string
    {
        $data = json_encode($payload, JSON_THROW_ON_ERROR);
        return hash_hmac('sha256', $data, $this->secretKey);
    }
}
