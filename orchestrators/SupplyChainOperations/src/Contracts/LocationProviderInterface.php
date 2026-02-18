<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface LocationProviderInterface
{
    public function getLocation(string $locationId): ?array;
}
