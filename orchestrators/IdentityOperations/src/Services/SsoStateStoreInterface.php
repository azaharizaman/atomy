<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Layer-2 contract for persisting SSO state between init and callback.
 */
interface SsoStateStoreInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function put(string $state, array $data, int $ttlSeconds): void;

    /**
     * @return array<string, mixed>|null
     */
    public function pull(string $state): ?array;
}

