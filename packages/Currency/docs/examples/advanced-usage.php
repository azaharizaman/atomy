<?php

declare(strict_types=1);

/**
 * Advanced Usage Examples for Nexus\Currency
 *
 * This file demonstrates complex scenarios including:
 * - Currency conversion
 * - Exchange rate management
 * - Historical rate queries
 * - Caching strategies
 * - Multi-currency calculations
 *
 * Prerequisites:
 * - CurrencyManagerInterface bound in DI container
 * - ExchangeRateProviderInterface implemented
 * - RateStorageInterface implemented
 * - Exchange rates available in storage
 */

use Nexus\Currency\Contracts\CurrencyManagerInterface;
use Nexus\Currency\Contracts\ExchangeRateProviderInterface;
use Nexus\Currency\Exceptions\ExchangeRateNotFoundException;

// Assume $currencyManager is injected via DI
/** @var CurrencyManagerInterface $currencyManager */
/** @var ExchangeRateProviderInterface $rateProvider */

// ============================================================================
// Example 1: Simple Currency Conversion
// ============================================================================

echo "=== Example 1: Currency Conversion ===\n";

try {
    // Convert 100 USD to MYR at current date
    $amountInMYR = $currencyManager->convert(
        amount: '100.00',
        fromCurrency: 'USD',
        toCurrency: 'MYR',
        effectiveDate: new \DateTimeImmutable()
    );
    
    echo "100.00 USD = {$amountInMYR} MYR\n";
    // Output: 100.00 USD = 450.00 MYR (assuming rate 4.50)
    
} catch (ExchangeRateNotFoundException $e) {
    echo "Exchange rate not available: {$e->getMessage()}\n";
}

echo "\n";

// ============================================================================
// Example 2: Historical Currency Conversion
// ============================================================================

echo "=== Example 2: Historical Conversion ===\n";

try {
    // Convert using historical rate from specific date
    $historicalDate = new \DateTimeImmutable('2024-01-01');
    
    $amountInEUR = $currencyManager->convert(
        amount: '1000.00',
        fromCurrency: 'MYR',
        toCurrency: 'EUR',
        effectiveDate: $historicalDate
    );
    
    echo "1000.00 MYR = {$amountInEUR} EUR (as of {$historicalDate->format('Y-m-d')})\n";
    // Output: 1000.00 MYR = 200.00 EUR (as of 2024-01-01)
    
} catch (ExchangeRateNotFoundException $e) {
    echo "Historical rate not available: {$e->getMessage()}\n";
}

echo "\n";

// ============================================================================
// Example 3: Multi-Currency Invoice Total Conversion
// ============================================================================

echo "=== Example 3: Multi-Currency Invoice Conversion ===\n";

$invoiceLines = [
    ['amount' => '100.00', 'currency' => 'USD'],
    ['amount' => '200.00', 'currency' => 'EUR'],
    ['amount' => '300.00', 'currency' => 'GBP'],
];

$baseCurrency = 'MYR';
$totalInBase = '0';
$conversionDate = new \DateTimeImmutable();

foreach ($invoiceLines as $line) {
    try {
        // Convert each line to base currency
        $convertedAmount = $currencyManager->convert(
            amount: $line['amount'],
            fromCurrency: $line['currency'],
            toCurrency: $baseCurrency,
            effectiveDate: $conversionDate
        );
        
        echo "{$line['amount']} {$line['currency']} = {$convertedAmount} {$baseCurrency}\n";
        
        // Sum in base currency
        $totalInBase = bcadd($totalInBase, $convertedAmount, 2);
        
    } catch (ExchangeRateNotFoundException $e) {
        echo "Cannot convert {$line['currency']}: {$e->getMessage()}\n";
    }
}

echo "Total in {$baseCurrency}: " . 
     $currencyManager->formatAmount($totalInBase, $baseCurrency) . "\n";
// Output: Total in MYR: 2,XXX.XX (sum of all converted amounts)

echo "\n";

// ============================================================================
// Example 4: Fetch Latest Exchange Rate
// ============================================================================

echo "=== Example 4: Fetch Latest Exchange Rate ===\n";

try {
    // Get latest rate from provider
    $latestRate = $rateProvider->getLatestRate(
        fromCurrency: 'USD',
        toCurrency: 'MYR'
    );
    
    echo "Latest USD/MYR rate: {$latestRate}\n";
    // Output: Latest USD/MYR rate: 4.5000
    
    // Use in conversion
    $convertedAmount = bcmul('100.00', $latestRate, 2);
    echo "100.00 USD = {$convertedAmount} MYR\n";
    
} catch (\Exception $e) {
    echo "Failed to fetch rate: {$e->getMessage()}\n";
}

echo "\n";

// ============================================================================
// Example 5: Fetch Historical Exchange Rate
// ============================================================================

echo "=== Example 5: Fetch Historical Rate ===\n";

try {
    $historicalDate = new \DateTimeImmutable('2024-06-15');
    
    $historicalRate = $rateProvider->getHistoricalRate(
        fromCurrency: 'EUR',
        toCurrency: 'MYR',
        date: $historicalDate
    );
    
    echo "EUR/MYR rate on {$historicalDate->format('Y-m-d')}: {$historicalRate}\n";
    // Output: EUR/MYR rate on 2024-06-15: 4.8500
    
} catch (\Exception $e) {
    echo "Failed to fetch historical rate: {$e->getMessage()}\n";
}

echo "\n";

// ============================================================================
// Example 6: Cross-Currency Conversion (USD -> GBP via MYR)
// ============================================================================

echo "=== Example 6: Cross-Currency Conversion ===\n";

try {
    // Convert USD to GBP via base currency (MYR)
    $date = new \DateTimeImmutable();
    
    // Step 1: USD to MYR
    $amountInMYR = $currencyManager->convert(
        amount: '100.00',
        fromCurrency: 'USD',
        toCurrency: 'MYR',
        effectiveDate: $date
    );
    
    // Step 2: MYR to GBP
    $amountInGBP = $currencyManager->convert(
        amount: $amountInMYR,
        fromCurrency: 'MYR',
        toCurrency: 'GBP',
        effectiveDate: $date
    );
    
    echo "100.00 USD -> {$amountInMYR} MYR -> {$amountInGBP} GBP\n";
    
} catch (ExchangeRateNotFoundException $e) {
    echo "Cross conversion failed: {$e->getMessage()}\n";
}

echo "\n";

// ============================================================================
// Example 7: Calculate Exchange Rate Difference (Forex Profit/Loss)
// ============================================================================

echo "=== Example 7: Forex Profit/Loss Calculation ===\n";

try {
    $purchaseDate = new \DateTimeImmutable('2024-01-01');
    $saleDate = new \DateTimeImmutable('2024-06-01');
    
    $originalAmount = '10000.00'; // USD
    
    // Convert at purchase date
    $amountAtPurchase = $currencyManager->convert(
        amount: $originalAmount,
        fromCurrency: 'USD',
        toCurrency: 'MYR',
        effectiveDate: $purchaseDate
    );
    
    // Convert at sale date
    $amountAtSale = $currencyManager->convert(
        amount: $originalAmount,
        fromCurrency: 'USD',
        toCurrency: 'MYR',
        effectiveDate: $saleDate
    );
    
    $difference = bcsub($amountAtSale, $amountAtPurchase, 2);
    
    echo "Original amount: {$originalAmount} USD\n";
    echo "Value on {$purchaseDate->format('Y-m-d')}: {$amountAtPurchase} MYR\n";
    echo "Value on {$saleDate->format('Y-m-d')}: {$amountAtSale} MYR\n";
    echo "Forex " . (bccomp($difference, '0', 2) >= 0 ? 'Gain' : 'Loss') . ": {$difference} MYR\n";
    
} catch (ExchangeRateNotFoundException $e) {
    echo "Cannot calculate forex difference: {$e->getMessage()}\n";
}

echo "\n";

// ============================================================================
// Example 8: Batch Currency Conversion with Caching
// ============================================================================

echo "=== Example 8: Batch Conversion with Caching ===\n";

$payments = [
    ['id' => 'INV-001', 'amount' => '500.00', 'currency' => 'USD'],
    ['id' => 'INV-002', 'amount' => '300.00', 'currency' => 'USD'],
    ['id' => 'INV-003', 'amount' => '750.00', 'currency' => 'EUR'],
    ['id' => 'INV-004', 'amount' => '200.00', 'currency' => 'EUR'],
    ['id' => 'INV-005', 'amount' => '1000.00', 'currency' => 'GBP'],
];

$conversionDate = new \DateTimeImmutable();
$baseCurrency = 'MYR';

// The CurrencyManager caches rates internally for the same from/to/date combination
foreach ($payments as $payment) {
    try {
        $convertedAmount = $currencyManager->convert(
            amount: $payment['amount'],
            fromCurrency: $payment['currency'],
            toCurrency: $baseCurrency,
            effectiveDate: $conversionDate
        );
        
        echo "{$payment['id']}: {$payment['amount']} {$payment['currency']} = {$convertedAmount} {$baseCurrency}\n";
        
    } catch (ExchangeRateNotFoundException $e) {
        echo "{$payment['id']}: Conversion failed - {$e->getMessage()}\n";
    }
}

echo "\n";

// ============================================================================
// Example 9: Handling Missing Exchange Rates Gracefully
// ============================================================================

echo "=== Example 9: Graceful Fallback for Missing Rates ===\n";

function convertWithFallback(
    CurrencyManagerInterface $manager,
    string $amount,
    string $fromCurrency,
    string $toCurrency,
    \DateTimeImmutable $date,
    ?string $fallbackRate = null
): string {
    try {
        return $manager->convert($amount, $fromCurrency, $toCurrency, $date);
        
    } catch (ExchangeRateNotFoundException $e) {
        if ($fallbackRate !== null) {
            // Use manual fallback rate
            $converted = bcmul($amount, $fallbackRate, 10);
            $toCurrencyObj = $manager->getCurrency($toCurrency);
            return $manager->formatAmount($converted, $toCurrency);
        }
        
        throw $e; // Re-throw if no fallback
    }
}

try {
    $converted = convertWithFallback(
        manager: $currencyManager,
        amount: '100.00',
        fromCurrency: 'USD',
        toCurrency: 'MYR',
        date: new \DateTimeImmutable('2020-01-01'), // Old date, might not have rate
        fallbackRate: '4.20' // Manual fallback
    );
    
    echo "Converted with fallback: {$converted} MYR\n";
    
} catch (ExchangeRateNotFoundException $e) {
    echo "Conversion failed even with fallback: {$e->getMessage()}\n";
}

echo "\n";

// ============================================================================
// Example 10: Real-Time vs Cached Rate Comparison
// ============================================================================

echo "=== Example 10: Real-Time vs Cached Rate ===\n";

try {
    // First call: fetches from provider and caches
    $startTime = microtime(true);
    $amount1 = $currencyManager->convert(
        amount: '1000.00',
        fromCurrency: 'USD',
        toCurrency: 'MYR',
        effectiveDate: new \DateTimeImmutable()
    );
    $duration1 = (microtime(true) - $startTime) * 1000;
    
    echo "First conversion (fetch + cache): {$amount1} MYR ({$duration1} ms)\n";
    
    // Second call: uses cached rate (faster)
    $startTime = microtime(true);
    $amount2 = $currencyManager->convert(
        amount: '2000.00',
        fromCurrency: 'USD',
        toCurrency: 'MYR',
        effectiveDate: new \DateTimeImmutable()
    );
    $duration2 = (microtime(true) - $startTime) * 1000;
    
    echo "Second conversion (cached): {$amount2} MYR ({$duration2} ms)\n";
    echo "Cache speedup: " . round($duration1 / $duration2, 2) . "x faster\n";
    
} catch (ExchangeRateNotFoundException $e) {
    echo "Conversion failed: {$e->getMessage()}\n";
}

echo "\n";

// ============================================================================
// Example 11: Currency Pair Value Object Usage
// ============================================================================

echo "=== Example 11: CurrencyPair Value Object ===\n";

use Nexus\Currency\ValueObjects\CurrencyPair;

try {
    $usd = $currencyManager->getCurrency('USD');
    $myr = $currencyManager->getCurrency('MYR');
    
    // Create currency pair
    $pair = new CurrencyPair(from: $usd, to: $myr);
    
    echo "Currency Pair: {$pair->getFrom()->getCode()}/{$pair->getTo()->getCode()}\n";
    // Output: Currency Pair: USD/MYR
    
    // Get inverse pair
    $inversePair = $pair->inverse();
    echo "Inverse Pair: {$inversePair->getFrom()->getCode()}/{$inversePair->getTo()->getCode()}\n";
    // Output: Inverse Pair: MYR/USD
    
    // Check equality
    $anotherPair = new CurrencyPair(from: $usd, to: $myr);
    echo "Pairs equal? " . ($pair->equals($anotherPair) ? 'Yes' : 'No') . "\n";
    // Output: Pairs equal? Yes
    
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

echo "\n";

// ============================================================================
// Example 12: Multi-Currency Balance Calculation
// ============================================================================

echo "=== Example 12: Multi-Currency Balance ===\n";

$accounts = [
    ['name' => 'USD Account', 'balance' => '5000.00', 'currency' => 'USD'],
    ['name' => 'EUR Account', 'balance' => '3000.00', 'currency' => 'EUR'],
    ['name' => 'GBP Account', 'balance' => '2000.00', 'currency' => 'GBP'],
    ['name' => 'MYR Account', 'balance' => '10000.00', 'currency' => 'MYR'],
];

$baseCurrency = 'MYR';
$totalBalance = '0';
$conversionDate = new \DateTimeImmutable();

echo "Account Balances (converted to {$baseCurrency}):\n";

foreach ($accounts as $account) {
    if ($account['currency'] === $baseCurrency) {
        $balanceInBase = $account['balance'];
    } else {
        try {
            $balanceInBase = $currencyManager->convert(
                amount: $account['balance'],
                fromCurrency: $account['currency'],
                toCurrency: $baseCurrency,
                effectiveDate: $conversionDate
            );
        } catch (ExchangeRateNotFoundException $e) {
            echo "  {$account['name']}: Cannot convert ({$e->getMessage()})\n";
            continue;
        }
    }
    
    $totalBalance = bcadd($totalBalance, $balanceInBase, 2);
    
    echo "  {$account['name']}: {$account['balance']} {$account['currency']} = {$balanceInBase} {$baseCurrency}\n";
}

echo "Total Balance: " . $currencyManager->formatAmount($totalBalance, $baseCurrency) . " {$baseCurrency}\n";

echo "\n";

// ============================================================================
// Example 13: Validate Conversion Before Processing
// ============================================================================

echo "=== Example 13: Pre-Conversion Validation ===\n";

function validateConversionPossible(
    CurrencyManagerInterface $manager,
    string $fromCurrency,
    string $toCurrency,
    \DateTimeImmutable $date
): bool {
    // Check both currencies exist
    if (!$manager->isValidCurrency($fromCurrency) || !$manager->isValidCurrency($toCurrency)) {
        return false;
    }
    
    // Try to convert a test amount
    try {
        $manager->convert('1.00', $fromCurrency, $toCurrency, $date);
        return true;
    } catch (ExchangeRateNotFoundException $e) {
        return false;
    }
}

$canConvert = validateConversionPossible(
    $currencyManager,
    'USD',
    'MYR',
    new \DateTimeImmutable()
);

echo "Can convert USD to MYR? " . ($canConvert ? 'Yes' : 'No') . "\n";

echo "\n";

// ============================================================================
// Example 14: Same-Currency "Conversion" (Identity)
// ============================================================================

echo "=== Example 14: Same-Currency Conversion ===\n";

try {
    // Converting to same currency returns formatted amount
    $sameAmount = $currencyManager->convert(
        amount: '1234.5678',
        fromCurrency: 'MYR',
        toCurrency: 'MYR',
        effectiveDate: new \DateTimeImmutable()
    );
    
    echo "1234.5678 MYR to MYR: {$sameAmount} MYR\n";
    // Output: 1234.5678 MYR to MYR: 1234.57 MYR (formatted to precision)
    
} catch (ExchangeRateNotFoundException $e) {
    echo "Unexpected error: {$e->getMessage()}\n";
}

echo "\n";

// ============================================================================
// Example 15: Custom Rate Provider Implementation Pattern
// ============================================================================

echo "=== Example 15: Custom Rate Provider Pattern ===\n";

echo <<<'EXAMPLE'
// Your custom implementation:

final readonly class MyCustomRateProvider implements ExchangeRateProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey
    ) {}
    
    public function getLatestRate(string $fromCurrency, string $toCurrency): string
    {
        $response = $this->httpClient->get("https://api.example.com/rates", [
            'query' => [
                'from' => $fromCurrency,
                'to' => $toCurrency,
                'api_key' => $this->apiKey,
            ],
        ]);
        
        $data = json_decode($response->getBody()->getContents(), true);
        
        return (string) $data['rate'];
    }
    
    public function getHistoricalRate(
        string $fromCurrency,
        string $toCurrency,
        \DateTimeImmutable $date
    ): string {
        $response = $this->httpClient->get("https://api.example.com/rates/historical", [
            'query' => [
                'from' => $fromCurrency,
                'to' => $toCurrency,
                'date' => $date->format('Y-m-d'),
                'api_key' => $this->apiKey,
            ],
        ]);
        
        $data = json_decode($response->getBody()->getContents(), true);
        
        return (string) $data['rate'];
    }
}

EXAMPLE;

echo "\n";

echo "=== All Advanced Examples Complete ===\n";
