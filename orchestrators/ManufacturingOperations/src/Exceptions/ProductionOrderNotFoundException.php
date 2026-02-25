<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\Exceptions;

final class ProductionOrderNotFoundException extends ManufacturingOperationsException
{
    public static function forOrder(string $orderId): self
    {
        return new self("Production order {$orderId} not found.");
    }
}
