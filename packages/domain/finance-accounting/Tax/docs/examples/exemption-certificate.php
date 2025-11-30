<?php

declare(strict_types=1);

/**
 * Exemption Certificate Example: Nexus\Tax
 * 
 * This example demonstrates:
 * - Partial tax exemption (50% agricultural exemption)
 * - Certificate validation and expiration
 * - Reduced tax calculation
 * - GL posting with exemption tracking
 */

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\Contracts\TaxCalculatorInterface;
use Nexus\Tax\Contracts\TaxExemptionManagerInterface;
use Nexus\Tax\Enums\TaxExemptionReason;
use Nexus\Tax\Enums\TaxType;
use Nexus\Tax\Exceptions\ExemptionCertificateExpiredException;
use Nexus\Tax\ValueObjects\ExemptionCertificate;
use Nexus\Tax\ValueObjects\TaxContext;

/** @var TaxCalculatorInterface $taxCalculator */
$taxCalculator = app(TaxCalculatorInterface::class);

/** @var TaxExemptionManagerInterface $exemptionManager */
$exemptionManager = app(TaxExemptionManagerInterface::class);

// Step 1: Create exemption certificate for agricultural customer
$certificate = new ExemptionCertificate(
    certificateId: 'CERT-AG-2024-001',
    customerId: 'CUST-FARM-789',
    reason: TaxExemptionReason::Agricultural,
    exemptionPercentage: '50.0000', // 50% exemption on farm equipment
    issueDate: new \DateTimeImmutable('2024-01-01'),
    expirationDate: new \DateTimeImmutable('2025-12-31'),
    issuingAuthority: 'US Department of Agriculture',
    jurisdictionCode: 'US-IA', // Iowa
    metadata: [
        'farm_id' => 'FARM-IA-12345',
        'certification_type' => 'Qualified Agricultural Producer',
    ]
);

// Step 2: Validate certificate is active
try {
    $isValid = $exemptionManager->isValid($certificate, new \DateTimeImmutable('2024-11-24'));
    echo "Certificate Validation: " . ($isValid ? "✅ VALID" : "❌ INVALID") . "\n\n";
} catch (ExemptionCertificateExpiredException $e) {
    echo "Certificate expired on {$e->getExpirationDate()->format('Y-m-d')}\n";
    exit(1);
}

// Step 3: Build tax context WITH exemption certificate
$context = new TaxContext(
    transactionId: 'INV-2024-FARM-001',
    transactionDate: new \DateTimeImmutable('2024-11-24'),
    taxCode: 'US-IA-SALES',
    taxType: TaxType::SalesTax,
    customerId: 'CUST-FARM-789',
    destinationAddress: [
        'line1' => '789 Farm Road',
        'city' => 'Des Moines',
        'state' => 'IA',
        'postal_code' => '50309',
        'country' => 'US',
    ],
    exemptionCertificate: $certificate // Include certificate
);

$netAmount = Money::of('1000.00', 'USD'); // Farm equipment purchase

// Step 4: Calculate tax (will apply 50% exemption)
$taxBreakdown = $taxCalculator->calculate($context, $netAmount);

// Step 5: Display results
echo "Tax Calculation with Agricultural Exemption\n";
echo "==========================================\n\n";

echo "Original Amount: {$netAmount->getAmount()} {$netAmount->getCurrency()}\n";
echo "Exemption: {$certificate->exemptionPercentage}% ({$certificate->reason->name})\n";
echo "Taxable Base: {$taxBreakdown->netAmount->getAmount()} {$taxBreakdown->netAmount->getCurrency()}\n";
echo "Tax Amount: {$taxBreakdown->totalTaxAmount->getAmount()} {$taxBreakdown->totalTaxAmount->getCurrency()}\n";
echo "Gross Amount: {$taxBreakdown->grossAmount->getAmount()} {$taxBreakdown->grossAmount->getCurrency()}\n\n";

echo "Tax Lines:\n";
foreach ($taxBreakdown->taxLines as $line) {
    echo sprintf(
        "  - %s: %s %s (Taxable Base: %s, Rate: %s%%)\n",
        $line->description,
        $line->amount->getAmount(),
        $line->amount->getCurrency(),
        $line->taxableBase->getAmount(),
        bcmul($line->rate->rate, '100', 4)
    );
}

// Step 6: Demonstrate GL posting with exemption tracking
echo "\nRecommended GL Posting:\n";
echo "  DR: Inventory/Equipment    1,000.00\n";
echo "  DR: Sales Tax              {$taxBreakdown->totalTaxAmount->getAmount()}\n";
echo "  CR: Accounts Payable       {$taxBreakdown->grossAmount->getAmount()}\n";
echo "  Memo: 50% agricultural exemption applied (CERT-AG-2024-001)\n";

/**
 * Expected Output:
 * 
 * Certificate Validation: ✅ VALID
 * 
 * Tax Calculation with Agricultural Exemption
 * ==========================================
 * 
 * Original Amount: 1000.00 USD
 * Exemption: 50.0000% (Agricultural)
 * Taxable Base: 500.00 USD
 * Tax Amount: 30.00 USD
 * Gross Amount: 530.00 USD
 * 
 * Tax Lines:
 *   - Iowa State Sales Tax (6%): 30.00 USD (Taxable Base: 500.00, Rate: 6.0000%)
 * 
 * Recommended GL Posting:
 *   DR: Inventory/Equipment    1,000.00
 *   DR: Sales Tax              30.00
 *   CR: Accounts Payable       1,030.00
 *   Memo: 50% agricultural exemption applied (CERT-AG-2024-001)
 * 
 * Calculation Breakdown:
 * - Original amount: $1,000.00
 * - Exemption: 50%
 * - Taxable base: $1,000.00 × (1 - 0.50) = $500.00
 * - Tax rate: 6%
 * - Tax amount: $500.00 × 0.06 = $30.00
 * - Without exemption: $1,000.00 × 0.06 = $60.00
 * - Tax savings: $30.00
 */
