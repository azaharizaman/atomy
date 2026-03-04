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
            if (
                is_array($snapshot) &&
                isset($snapshot['status']) &&
                (is_string($snapshot['status']) || is_scalar($snapshot['status']))
            ) {
                $status = trim((string) $snapshot['status']);
                $statuses[$providerId] = $status !== '' ? $status : 'unknown';
                continue;
            }

            $statuses[$providerId] = 'unknown';
        }

        return $statuses;
    }
}
