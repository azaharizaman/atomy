<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\Crypto\Contracts\SecureIdGeneratorInterface as BaseSecureIdGeneratorInterface;

/**
 * Interface for generating secure, cryptographically random identifiers for Procurement operations.
 *
 * This interface extends the base Nexus\Crypto SecureIdGeneratorInterface,
 * adding procurement-specific methods for API credentials and session tokens.
 *
 * Inherited from base interface:
 * - generateId(string $prefix = '', int $length = 16): string
 * - generateUuid(): string
 * - randomBytes(int $length): string
 * - randomHex(int $length): string
 *
 * Procurement-specific extensions:
 * - generateClientId(string $entityId): string
 * - generateClientSecret(int $length = 32): string
 * - generateSessionToken(int $length = 32): string
 *
 * @package Nexus\ProcurementOperations
 * @since 1.0.0
 * @see BaseSecureIdGeneratorInterface
 */
interface SecureIdGeneratorInterface extends BaseSecureIdGeneratorInterface
{
    /**
     * Generate a secure API client ID.
     *
     * Generates a deterministic but secure client ID derived from an entity ID.
     * The client ID is suitable for OAuth2 client credentials flow and
     * vendor portal integrations.
     *
     * The generation uses HMAC-based derivation to ensure:
     * - Consistent ID for the same entity (deterministic)
     * - Cannot reverse-engineer the original entity ID
     * - Suitable for public exposure (unlike secrets)
     *
     * @param string $entityId The entity ID to derive from (e.g., vendor_id, supplier_id)
     * @return string Deterministic but secure client ID (typically 32-64 chars)
     */
    public function generateClientId(string $entityId): string;

    /**
     * Generate a secure API client secret.
     *
     * Generates a high-entropy secret suitable for:
     * - OAuth2 client credentials
     * - API authentication
     * - Webhook signing keys
     *
     * The secret is generated using CSPRNG and is NOT deterministic.
     * Each call produces a unique secret.
     *
     * @param int $length Secret length in bytes (default: 32, range: 16-64)
     * @return string High-entropy secret (base64url encoded, length varies)
     * @throws \InvalidArgumentException If length is out of valid range
     */
    public function generateClientSecret(int $length = 32): string;

    /**
     * Generate a secure session token.
     *
     * Generates a cryptographically secure token suitable for:
     * - Session identifiers
     * - CSRF tokens
     * - Temporary authentication tokens
     *
     * The token is URL-safe and suitable for inclusion in URLs or headers.
     *
     * @param int $length Token length in bytes (default: 32, range: 16-128)
     * @return string URL-safe session token
     * @throws \InvalidArgumentException If length is out of valid range
     */
    public function generateSessionToken(int $length = 32): string;
}
