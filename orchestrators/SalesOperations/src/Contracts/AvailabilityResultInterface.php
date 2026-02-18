<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Contracts;

interface AvailabilityResultInterface
{
    public function isAvailable(): bool;

    public function getAvailableQuantity(): float;

    public function getRequestedQuantity(): float;

    public function getShortageQuantity(): float;

    public function getWarehouseId(): string;
}
