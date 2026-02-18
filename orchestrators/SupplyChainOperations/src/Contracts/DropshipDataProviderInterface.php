<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface DropshipDataProviderInterface
{
    public function hasDropshipLines(mixed $order): bool;

    public function getDropshipLines(mixed $order): array;

    public function getDropshipVendorId(mixed $order): ?string;
}
