<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Nexus\Tax
 * 
 * This example shows the simplest tax calculation scenario:
 * - Single jurisdiction (US-CA)
 * - Standard sales tax
 * - No exemptions
 * - Physical goods
 */

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\Contracts\TaxCalculatorInterface;
use Nexus\Tax\Enums\TaxType;
use Nexus\Tax\ValueObjects\TaxContext;

// Assume Laravel service container resolves TaxCalculatorInterface
// to a configured instance with repository implementations
/** @var TaxCalculatorInterface $taxCalculator */
$taxCalculator = app(TaxCalculatorInterface::class);

// Build tax context for a California sales transaction
$context = new TaxContext(
    transactionId: 'INV-2024-00123',
    transactionDate: new \DateTimeImmutable('2024-11-24'),
    taxCode: 'US-CA-SALES',
    taxType: TaxType::SalesTax,
    customerId: 'CUST-00456',
    destinationAddress: [
        'line1' => '123 Main St',
        'city' => 'San Francisco',
        'state' => 'CA',
        'postal_code' => '94102',
        'country' => 'US',
    ]
);

// Amount before tax
$netAmount = Money::of('100.00', 'USD');

// Calculate tax
$taxBreakdown = $taxCalculator->calculate($context, $netAmount);

// Display results
echo "Net Amount: {$taxBreakdown->netAmount->getAmount()} {$taxBreakdown->netAmount->getCurrency()}\n";
echo "Total Tax: {$taxBreakdown->totalTaxAmount->getAmount()} {$taxBreakdown->totalTaxAmount->getCurrency()}\n";
echo "Gross Amount: {$taxBreakdown->grossAmount->getAmount()} {$taxBreakdown->grossAmount->getCurrency()}\n";

echo "\nTax Breakdown:\n";
foreach ($taxBreakdown->taxLines as $line) {
    echo sprintf(
        "  - %s: %s %s (Rate: %s%%, GL: %s)\n",
        $line->description,
        $line->amount->getAmount(),
        $line->amount->getCurrency(),
        bcmul($line->rate->rate, '100', 4),
        $line->glAccountCode
    );
}

/**
 * Expected Output:
 * 
 * Net Amount: 100.00 USD
 * Total Tax: 7.25 USD
 * Gross Amount: 107.25 USD
 * 
 * Tax Breakdown:
 *   - California State Sales Tax: 7.25 USD (Rate: 7.2500%, GL: 2100-TAX-CA)
 */
