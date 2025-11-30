<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Contracts;

/**
 * Bin location interface
 */
interface BinLocationInterface
{
    public function getId(): string;
    public function getCode(): string;
    public function getWarehouseId(): string;
    public function getCoordinates(): ?array; // ['latitude' => float, 'longitude' => float]
}
