<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\DataProviders;

use Nexus\ConnectivityOperations\Contracts\ProviderHealthStoreInterface;

final readonly class ProviderHealthDataProvider
{
    public function __construct(private ProviderHealthStoreInterface $healthStore) {}

    /**
     * @return array<string, string>
     */
    public function statuses(): array
    {
        $snapshots = $this->healthStore->all();
        $statuses = [];

        foreach ($snapshots as $providerId => $snapshot) {
            $statuses[$providerId] = (string) ($snapshot['status'] ?? 'unknown');
        }

        return $statuses;
    }
}
