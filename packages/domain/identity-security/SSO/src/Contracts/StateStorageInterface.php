<?php

declare(strict_types=1);

namespace Nexus\SSO\Contracts;

/**
 * State storage interface
 * 
 * Stores temporary CSRF state tokens for SSO callbacks
 */
interface StateStorageInterface
{
    /**
     * Store state token with metadata
     * 
     * @param string $token State token
     * @param array<string, mixed> $metadata Metadata to store
     * @param int $ttlSeconds Time to live in seconds
     */
    public function store(string $token, array $metadata, int $ttlSeconds): void;

    /**
     * Retrieve state metadata
     * 
     * @param string $token State token
     * @return array<string, mixed>|null Metadata or null if not found/expired
     */
    public function retrieve(string $token): ?array;

    /**
     * Delete state token
     */
    public function delete(string $token): void;

    /**
     * Check if state exists
     */
    public function exists(string $token): bool;
}
