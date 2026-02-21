<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Exceptions;

/**
 * Product Cost Not Found Exception
 * 
 * Thrown when a requested product cost cannot be found.
 */
class ProductCostNotFoundException extends CostAccountingException
{
    public function __construct(
        private string $productId,
        private ?string $periodId = null
    ) {
        $message = sprintf('Product cost not found for product: %s', $productId);
        if ($periodId !== null) {
            $message .= sprintf(' in period: %s', $periodId);
        }
        parent::__construct($message);
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getPeriodId(): ?string
    {
        return $this->periodId;
    }
}
