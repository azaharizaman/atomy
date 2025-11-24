<?php

declare(strict_types=1);

/**
 * Multi-Jurisdiction Example: Nexus\Tax
 * 
 * This example demonstrates hierarchical tax calculation:
 * - Federal + State + Local taxes
 * - Cascading tax structure (tax on tax)
 * - Multiple tax lines with children
 * - Canadian HST (Harmonized Sales Tax) scenario
 */

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\Contracts\TaxCalculatorInterface;
use Nexus\Tax\Enums\TaxType;
use Nexus\Tax\ValueObjects\TaxContext;

/** @var TaxCalculatorInterface $taxCalculator */
$taxCalculator = app(TaxCalculatorInterface::class);

// Build context for Ontario, Canada transaction (13% HST)
// HST = 5% Federal GST + 8% Provincial PST
$context = new TaxContext(
    transactionId: 'INV-2024-00789',
    transactionDate: new \DateTimeImmutable('2024-11-24'),
    taxCode: 'CA-ON-HST',
    taxType: TaxType::VAT, // HST is type of VAT
    customerId: 'CUST-CA-001',
    destinationAddress: [
        'line1' => '456 King St W',
        'city' => 'Toronto',
        'state' => 'ON',
        'postal_code' => 'M5V 1K4',
        'country' => 'CA',
    ]
);

$netAmount = Money::of('200.00', 'CAD');

$taxBreakdown = $taxCalculator->calculate($context, $netAmount);

echo "Multi-Jurisdiction Tax Calculation: Canadian HST\n";
echo "================================================\n\n";

echo "Net Amount: {$taxBreakdown->netAmount->getAmount()} {$taxBreakdown->netAmount->getCurrency()}\n";
echo "Total Tax: {$taxBreakdown->totalTaxAmount->getAmount()} {$taxBreakdown->totalTaxAmount->getCurrency()}\n";
echo "Gross Amount: {$taxBreakdown->grossAmount->getAmount()} {$taxBreakdown->grossAmount->getCurrency()}\n\n";

// Recursive function to display tax lines with children
function displayTaxLines(array $taxLines, int $indent = 0): void
{
    $prefix = str_repeat('  ', $indent);
    
    foreach ($taxLines as $line) {
        echo sprintf(
            "%s- %s: %s %s (Rate: %s%%, Level: %s, GL: %s)\n",
            $prefix,
            $line->description,
            $line->amount->getAmount(),
            $line->amount->getCurrency(),
            bcmul($line->rate->rate, '100', 4),
            $line->rate->level->name,
            $line->glAccountCode
        );
        
        if (!empty($line->children)) {
            echo "{$prefix}  Children:\n";
            displayTaxLines($line->children, $indent + 2);
        }
    }
}

echo "Tax Breakdown:\n";
displayTaxLines($taxBreakdown->taxLines);

/**
 * Expected Output:
 * 
 * Multi-Jurisdiction Tax Calculation: Canadian HST
 * ================================================
 * 
 * Net Amount: 200.00 CAD
 * Total Tax: 26.00 CAD
 * Gross Amount: 226.00 CAD
 * 
 * Tax Breakdown:
 * - Federal GST (5%): 10.00 CAD (Rate: 5.0000%, Level: Federal, GL: 2100-TAX-GST)
 * - Provincial PST Ontario (8%): 16.00 CAD (Rate: 8.0000%, Level: State, GL: 2100-TAX-PST-ON)
 */
