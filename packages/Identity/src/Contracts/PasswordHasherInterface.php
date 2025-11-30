<?php

declare(strict_types=1);

namespace Nexus\Identity\Contracts;

/**
 * Password hasher interface
 * 
 * Handles secure password hashing and verification
 */
interface PasswordHasherInterface
{
    /**
     * Hash a plain-text password
     * 
     * @param string $password Plain-text password
     * @return string Hashed password
     */
    public function hash(string $password): string;

    /**
     * Verify a plain-text password against a hash
     * 
     * @param string $password Plain-text password
     * @param string $hash Hashed password
     * @return bool True if password matches hash
     */
    public function verify(string $password, string $hash): bool;

    /**
     * Check if a hash needs to be rehashed (algorithm changed)
     * 
     * @param string $hash Hashed password
     * @return bool True if hash needs rehashing
     */
    public function needsRehash(string $hash): bool;
}
