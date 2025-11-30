<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

use Nexus\Identity\ValueObjects\ApiToken;

/**
 * Token manager interface
 * 
 * Handles API token generation and validation
 */
interface TokenManagerInterface
{
    /**
     * Generate a new API token for a user
     * 
     * @param string $userId User identifier
     * @param string $name Token name (for identification)
     * @param string[] $scopes Token scopes (permissions)
     * @param \DateTimeInterface|null $expiresAt Token expiration (null = permanent)
     * @return ApiToken Generated API token
     */
    public function generateToken(
        string $userId,
        string $name,
        array $scopes = [],
        ?\DateTimeInterface $expiresAt = null
    ): ApiToken;

    /**
     * Validate an API token
     * 
     * @param string $token API token
     * @return UserInterface Token owner
     * @throws \Nexus\Identity\Exceptions\InvalidTokenException
     */
    public function validateToken(string $token): UserInterface;

    /**
     * Check if a token is valid
     */
    public function isValid(string $token): bool;

    /**
     * Revoke a specific token
     */
    public function revokeToken(string $tokenId): void;

    /**
     * Revoke all tokens for a user
     */
    public function revokeAllTokens(string $userId): void;

    /**
     * Get all tokens for a user
     * 
     * @return array<array<string, mixed>> Array of token metadata
     */
    public function getUserTokens(string $userId): array;

    /**
     * Get token scopes (permissions)
     * 
     * @return string[]
     */
    public function getTokenScopes(string $token): array;

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens(): int;
}
