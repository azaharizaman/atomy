<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Exceptions;

/**
 * Exception thrown when insufficient materials are available.
 */
class InsufficientMaterialException extends \RuntimeException
{
    /**
     * @var array<string, array{required: float, available: float}> Shortage details
     */
    private array $shortages;

    /**
     * @param array<string, array{required: float, available: float}> $shortages
     */
    public function __construct(
        string $message = 'Insufficient materials',
        array $shortages = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->shortages = $shortages;
    }

    public static function forProduct(string $productId, float $required, float $available): self
    {
        $shortage = $required - $available;
        return new self(
            "Insufficient material for product '{$productId}'. " .
            "Required: {$required}, Available: {$available}, Short: {$shortage}",
            [$productId => ['required' => $required, 'available' => $available]]
        );
    }

    /**
     * Create exception with multiple shortages.
     *
     * @param array<string, array{required: float, available: float}> $shortages
     */
    public static function withShortages(array $shortages): self
    {
        $count = count($shortages);
        $products = implode(', ', array_keys($shortages));
        return new self(
            "{$count} product(s) have insufficient materials: {$products}",
            $shortages
        );
    }

    /**
     * Get shortage details.
     *
     * @return array<string, array{required: float, available: float}>
     */
    public function getShortages(): array
    {
        return $this->shortages;
    }

    /**
     * Get total shortage quantity.
     */
    public function getTotalShortage(): float
    {
        $total = 0.0;
        foreach ($this->shortages as $shortage) {
            $total += max(0, $shortage['required'] - $shortage['available']);
        }
        return $total;
    }
}
