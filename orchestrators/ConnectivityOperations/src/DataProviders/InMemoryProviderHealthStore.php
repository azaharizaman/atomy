<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\DataProviders;

use Nexus\ConnectivityOperations\Contracts\ProviderHealthStoreInterface;

final class InMemoryProviderHealthStore implements ProviderHealthStoreInterface
{
    /** @var array<string, array<string, mixed>> */
    private array $data = [];

    public function record(string $providerId, array $snapshot): void
    {
        $this->data[$providerId] = $snapshot;
    }

    public function all(): array
    {
        return $this->data;
    }
}
