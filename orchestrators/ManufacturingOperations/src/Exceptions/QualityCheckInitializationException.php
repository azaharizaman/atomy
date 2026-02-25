<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\Exceptions;

final class QualityCheckInitializationException extends ManufacturingOperationsException
{
    public static function forOrder(string $orderId, string $reason): self
    {
        return new self("Failed to initialize quality check for order {$orderId}: {$reason}");
    }
}
