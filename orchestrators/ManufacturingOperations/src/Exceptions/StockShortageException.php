<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\Exceptions;

final class StockShortageException extends ManufacturingOperationsException
{
    /**
     * @param array<string, float>|string $shortages [sku => missing_quantity] or reason
     */
    public static function forShortages(array|string $shortages): self
    {
        if (is_string($shortages)) {
            return new self($shortages);
        }
        
        if (empty($shortages)) {
            return new self("Stock shortage encountered: No details provided.");
        }
        
        $details = implode(', ', array_map(
            fn($sku, $qty) => sprintf("%s: %s", $sku, number_format((float)$qty, 4, '.', '')), 
            array_keys($shortages), 
            $shortages
        ));
        return new self("Stock shortage encountered: {$details}");
    }
}
