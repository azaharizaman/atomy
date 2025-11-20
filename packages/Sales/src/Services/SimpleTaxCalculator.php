<?php

declare(strict_types=1);

namespace Nexus\Sales\Services;

use Nexus\Sales\Contracts\TaxCalculatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Simple tax calculator (V1 implementation).
 * 
 * Returns configurable fixed tax rate for all products.
 * Phase 2: Replace with tax jurisdiction engine.
 */
final readonly class SimpleTaxCalculator implements TaxCalculatorInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private float $defaultTaxRate = 0.0
    ) {}

    /**
     * {@inheritDoc}
     */
    public function calculateLineTax(
        string $tenantId,
        string $productVariantId,
        float $lineSubtotal,
        string $customerId,
        string $currencyCode
    ): float {
        $taxAmount = round($lineSubtotal * ($this->defaultTaxRate / 100), 2);

        $this->logger->debug('Tax calculated', [
            'tenant_id' => $tenantId,
            'product_variant_id' => $productVariantId,
            'line_subtotal' => $lineSubtotal,
            'tax_rate' => $this->defaultTaxRate,
            'tax_amount' => $taxAmount,
        ]);

        return $taxAmount;
    }

    /**
     * {@inheritDoc}
     */
    public function getTaxRate(
        string $tenantId,
        string $productVariantId,
        string $customerId
    ): float {
        return $this->defaultTaxRate;
    }
}
