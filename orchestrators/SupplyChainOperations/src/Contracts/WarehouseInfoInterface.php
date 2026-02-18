<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface WarehouseInfoInterface
{
    public function getId(): string;

    public function getRegionId(): ?string;

    public function getLocationId(): ?string;
}
