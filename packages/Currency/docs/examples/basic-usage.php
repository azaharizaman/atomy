<?php

declare(strict_types=1);

/**
 * Basic Usage Examples for Nexus\Currency
 *
 * This file demonstrates fundamental operations with the Currency package.
 *
 * Prerequisites:
 * - CurrencyManagerInterface is bound in your DI container
 * - Currency repository has been implemented
 * - Database contains seeded currencies (MYR, USD, EUR, etc.)
 */

use Nexus\Currency\Contracts\CurrencyManagerInterface;
use Nexus\Currency\Exceptions\CurrencyNotFoundException;
use Nexus\Currency\Exceptions\InvalidCurrencyException;

// Assume $currencyManager is injected via DI
/** @var CurrencyManagerInterface $currencyManager */

// ============================================================================
// Example 1: Validate Currency Codes
// ============================================================================

echo "=== Example 1: Currency Validation ===\n";

// Check if currency code is valid (ISO 4217 format + exists in system)
$isValidMYR = $currencyManager->isValidCurrency('MYR');
echo "Is MYR valid? " . ($isValidMYR ? 'Yes' : 'No') . "\n";
// Output: Is MYR valid? Yes

$isValidXXX = $currencyManager->isValidCurrency('XXX');
echo "Is XXX valid? " . ($isValidXXX ? 'Yes' : 'No') . "\n";
// Output: Is XXX valid? No

$isValidInvalid = $currencyManager->isValidCurrency('INVALID');
echo "Is INVALID valid? " . ($isValidInvalid ? 'Yes' : 'No') . "\n";
// Output: Is INVALID valid? No (not ISO 4217 format)

echo "\n";

// ============================================================================
// Example 2: Retrieve Currency Information
// ============================================================================

echo "=== Example 2: Retrieve Currency ===\n";

try {
    $myr = $currencyManager->getCurrency('MYR');
    echo "Currency Code: {$myr->getCode()}\n";
    echo "Decimal Places: {$myr->getDecimalPlaces()}\n";
    // Output:
    // Currency Code: MYR
    // Decimal Places: 2
    
} catch (CurrencyNotFoundException $e) {
    echo "Error: {$e->getMessage()}\n";
}

echo "\n";

// ============================================================================
// Example 3: Format Amounts with Correct Decimal Precision
// ============================================================================

echo "=== Example 3: Format Amounts ===\n";

// Format MYR amount (2 decimal places)
$formattedMYR = $currencyManager->formatAmount(
    amount: '1234.5678',
    currencyCode: 'MYR'
);
echo "MYR: {$formattedMYR}\n";
// Output: MYR: 1234.57 (rounded to 2 decimals)

// Format JPY amount (0 decimal places)
try {
    $formattedJPY = $currencyManager->formatAmount(
        amount: '1234.5678',
        currencyCode: 'JPY'
    );
    echo "JPY: {$formattedJPY}\n";
    // Output: JPY: 1235 (rounded to 0 decimals)
} catch (CurrencyNotFoundException $e) {
    echo "JPY not available in system\n";
}

// Format KWD amount (3 decimal places)
try {
    $formattedKWD = $currencyManager->formatAmount(
        amount: '1234.56789',
        currencyCode: 'KWD'
    );
    echo "KWD: {$formattedKWD}\n";
    // Output: KWD: 1234.568 (rounded to 3 decimals)
} catch (CurrencyNotFoundException $e) {
    echo "KWD not available in system\n";
}

// Format with very high precision (e.g., crypto)
try {
    $formattedBTC = $currencyManager->formatAmount(
        amount: '0.12345678',
        currencyCode: 'BTC'
    );
    echo "BTC: {$formattedBTC}\n";
    // Output: BTC: 0.1235 (rounded to 4 decimals if BTC configured with 4)
} catch (CurrencyNotFoundException $e) {
    echo "BTC not available in system\n";
}

echo "\n";

// ============================================================================
// Example 4: List All Active Currencies
// ============================================================================

echo "=== Example 4: List All Currencies ===\n";

$allCurrencies = $currencyManager->getAllCurrencies();

echo "Active currencies:\n";
foreach ($allCurrencies as $currency) {
    echo "  - {$currency->getCode()} ({$currency->getDecimalPlaces()} decimals)\n";
}
// Output:
//   - MYR (2 decimals)
//   - USD (2 decimals)
//   - EUR (2 decimals)
//   - GBP (2 decimals)
//   - JPY (0 decimals)
//   - KWD (3 decimals)
//   ...

echo "\n";

// ============================================================================
// Example 5: Handle Invalid Currency Gracefully
// ============================================================================

echo "=== Example 5: Error Handling ===\n";

try {
    $invalidCurrency = $currencyManager->getCurrency('INVALID');
} catch (InvalidCurrencyException $e) {
    echo "Caught InvalidCurrencyException: {$e->getMessage()}\n";
    // Output: Caught InvalidCurrencyException: Invalid currency code: INVALID
}

try {
    $notFoundCurrency = $currencyManager->getCurrency('XYZ');
} catch (CurrencyNotFoundException $e) {
    echo "Caught CurrencyNotFoundException: {$e->getMessage()}\n";
    // Output: Caught CurrencyNotFoundException: Currency not found: XYZ
}

echo "\n";

// ============================================================================
// Example 6: Validate User Input (Real-World Scenario)
// ============================================================================

echo "=== Example 6: Validate User Input ===\n";

function validateInvoiceCurrency(CurrencyManagerInterface $manager, string $userInput): void
{
    // Sanitize input
    $currencyCode = strtoupper(trim($userInput));
    
    // Validate format and existence
    if (!$manager->isValidCurrency($currencyCode)) {
        throw new \InvalidArgumentException(
            "Invalid or unsupported currency: {$currencyCode}"
        );
    }
    
    // Retrieve currency for further use
    $currency = $manager->getCurrency($currencyCode);
    
    echo "✓ Currency validated: {$currency->getCode()}\n";
    echo "  Decimal places: {$currency->getDecimalPlaces()}\n";
}

try {
    validateInvoiceCurrency($currencyManager, 'myr'); // Lowercase input
    // Output:
    // ✓ Currency validated: MYR
    //   Decimal places: 2
    
} catch (\InvalidArgumentException $e) {
    echo "Validation failed: {$e->getMessage()}\n";
}

try {
    validateInvoiceCurrency($currencyManager, 'INVALID');
} catch (\InvalidArgumentException $e) {
    echo "Validation failed: {$e->getMessage()}\n";
    // Output: Validation failed: Invalid or unsupported currency: INVALID
}

echo "\n";

// ============================================================================
// Example 7: Format Invoice Amount for Display
// ============================================================================

echo "=== Example 7: Invoice Amount Formatting ===\n";

function formatInvoiceAmount(
    CurrencyManagerInterface $manager,
    string $amount,
    string $currencyCode
): string {
    // Validate currency
    if (!$manager->isValidCurrency($currencyCode)) {
        throw new \InvalidArgumentException("Invalid currency: {$currencyCode}");
    }
    
    // Format amount with correct precision
    $formatted = $manager->formatAmount($amount, $currencyCode);
    
    // Return with currency symbol (you would implement symbol lookup separately)
    return "{$currencyCode} {$formatted}";
}

echo formatInvoiceAmount($currencyManager, '1234.5678', 'MYR') . "\n";
// Output: MYR 1234.57

echo formatInvoiceAmount($currencyManager, '9876.54321', 'USD') . "\n";
// Output: USD 9876.54

echo "\n";

// ============================================================================
// Example 8: Calculate Total with Mixed Currencies (Without Conversion)
// ============================================================================

echo "=== Example 8: Calculate Totals ===\n";

$lineItems = [
    ['amount' => '100.505', 'currency' => 'MYR'],
    ['amount' => '200.499', 'currency' => 'MYR'],
    ['amount' => '50.501', 'currency' => 'MYR'],
];

$total = '0';
foreach ($lineItems as $item) {
    $formattedAmount = $currencyManager->formatAmount(
        $item['amount'],
        $item['currency']
    );
    $total = bcadd($total, $formattedAmount, 2);
}

echo "Total (MYR): " . $currencyManager->formatAmount($total, 'MYR') . "\n";
// Output: Total (MYR): 351.01
// (100.51 + 200.50 + 50.50 = 351.01 after proper rounding)

echo "\n";

// ============================================================================
// Example 9: Validate Amount Before Storage
// ============================================================================

echo "=== Example 9: Validate Before Storage ===\n";

function validateAndStoreAmount(
    CurrencyManagerInterface $manager,
    string $amount,
    string $currencyCode
): array {
    // Validate currency
    if (!$manager->isValidCurrency($currencyCode)) {
        throw new \InvalidArgumentException("Invalid currency: {$currencyCode}");
    }
    
    // Format amount (this validates and rounds to correct precision)
    $formattedAmount = $manager->formatAmount($amount, $currencyCode);
    
    // Validate amount is positive
    if (bccomp($formattedAmount, '0', 10) <= 0) {
        throw new \InvalidArgumentException("Amount must be positive");
    }
    
    return [
        'amount' => $formattedAmount,
        'currency' => $currencyCode,
    ];
}

try {
    $validated = validateAndStoreAmount($currencyManager, '1234.5678', 'MYR');
    echo "Validated: {$validated['amount']} {$validated['currency']}\n";
    // Output: Validated: 1234.57 MYR
    
} catch (\InvalidArgumentException $e) {
    echo "Validation failed: {$e->getMessage()}\n";
}

try {
    $validated = validateAndStoreAmount($currencyManager, '-100.00', 'MYR');
} catch (\InvalidArgumentException $e) {
    echo "Validation failed: {$e->getMessage()}\n";
    // Output: Validation failed: Amount must be positive
}

echo "\n";

// ============================================================================
// Example 10: Currency-Aware Decimal Comparison
// ============================================================================

echo "=== Example 10: Decimal Comparison ===\n";

function compareAmounts(
    CurrencyManagerInterface $manager,
    string $amount1,
    string $amount2,
    string $currencyCode
): int {
    // Format both amounts to currency precision
    $formatted1 = $manager->formatAmount($amount1, $currencyCode);
    $formatted2 = $manager->formatAmount($amount2, $currencyCode);
    
    // Compare using bccomp
    // Returns: 1 if $1 > $2, -1 if $1 < $2, 0 if equal
    return bccomp($formatted1, $formatted2, 10);
}

$result = compareAmounts($currencyManager, '100.505', '100.495', 'MYR');
echo "100.505 vs 100.495 (MYR): ";
echo $result > 0 ? "Greater\n" : ($result < 0 ? "Less\n" : "Equal\n");
// Output: Greater (100.51 > 100.50 after rounding)

$result = compareAmounts($currencyManager, '100.504', '100.505', 'MYR');
echo "100.504 vs 100.505 (MYR): ";
echo $result > 0 ? "Greater\n" : ($result < 0 ? "Less\n" : "Equal\n");
// Output: Equal (both round to 100.50 for MYR)

echo "\n";

echo "=== All Examples Complete ===\n";
