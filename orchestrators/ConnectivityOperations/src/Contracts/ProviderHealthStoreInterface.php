<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\Contracts;

interface ProviderHealthStoreInterface
{
    /**
     * @param array<string, mixed> $snapshot
     */
    public function record(string $providerId, array $snapshot): void;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array;
}
