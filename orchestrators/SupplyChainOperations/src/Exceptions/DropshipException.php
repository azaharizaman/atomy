<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Exceptions;

/**
 * Exception thrown during dropship operations.
 */
final class DropshipException extends \RuntimeException
{
    public static function noVendorFound(string $productId): self
    {
        return new self("No vendor found for product {$productId}");
    }

    public static function vendorResolutionFailed(string $orderId, string $reason): self
    {
        return new self("Failed to resolve vendor for order {$orderId}: {$reason}");
    }

    public static function poCreationFailed(string $reason): self
    {
        return new self("Failed to create purchase order: {$reason}");
    }
}
