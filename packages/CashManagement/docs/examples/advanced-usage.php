<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: CashManagement
 * 
 * This example demonstrates:
 * 1. Cash flow forecasting with multiple scenarios
 * 2. Manual reconciliation with tolerance settings
 * 3. High-value transaction workflow escalation
 * 4. AI feedback loop for model improvement
 * 5. Multi-currency operations (V2)
 * 6. Cash position tracking
 */

use Nexus\CashManagement\Contracts\CashFlowForecastInterface;
use Nexus\CashManagement\Contracts\ReconciliationEngineInterface;
use Nexus\CashManagement\Contracts\CashPositionInterface;
use Nexus\CashManagement\Contracts\PendingAdjustmentInterface;
use Nexus\CashManagement\ValueObjects\ScenarioParametersVO;
use Nexus\CashManagement\ValueObjects\ReconciliationTolerance;
use Nexus\CashManagement\Enums\ForecastScenarioType;
use Nexus\Intelligence\Contracts\ClassificationServiceInterface;
use Nexus\Workflow\Contracts\WorkflowEngineInterface;

// ============================================
// Scenario 1: Cash Flow Forecasting
// ============================================

$cashFlowForecast = app(CashFlowForecastInterface::class);

// Generate baseline forecast (90 days)
$baselineParams = ScenarioParametersVO::fromScenarioType(
    scenarioType: ForecastScenarioType::BASELINE,
    horizonDays: 90
);

$baselineForecast = $cashFlowForecast->forecast(
    tenantId: 'tenant_abc123',
    parameters: $baselineParams,
    bankAccountId: null // Consolidated across all bank accounts
);

echo "=== BASELINE FORECAST (90 Days) ===\n";
echo "Min Balance: MYR {$baselineForecast->getMinBalance()}\n";
echo "Max Balance: MYR {$baselineForecast->getMaxBalance()}\n";
echo "Has Negative: " . ($baselineForecast->hasNegativeBalance() ? 'YES' : 'NO') . "\n";

if ($baselineForecast->hasNegativeBalance()) {
    echo "⚠ LIQUIDITY RISK: First negative date: {$baselineForecast->getFirstNegativeDate()->format('Y-m-d')}\n";
    
    // Alert finance team
    event(new \App\Events\LiquidityRiskDetected(
        tenantId: 'tenant_abc123',
        forecast: $baselineForecast
    ));
}

// Generate pessimistic scenario
$pessimisticParams = ScenarioParametersVO::fromScenarioType(
    scenarioType: ForecastScenarioType::PESSIMISTIC,
    horizonDays: 90
);

$pessimisticForecast = $cashFlowForecast->forecast(
    tenantId: 'tenant_abc123',
    parameters: $pessimisticParams
);

echo "\n=== PESSIMISTIC FORECAST (90 Days) ===\n";
echo "Min Balance: MYR {$pessimisticForecast->getMinBalance()}\n";
echo "Has Negative: " . ($pessimisticForecast->hasNegativeBalance() ? 'YES' : 'NO') . "\n";

// Compare scenarios
$balanceDifference = bcsub(
    $baselineForecast->getMinBalance(),
    $pessimisticForecast->getMinBalance(),
    2
);

echo "\n=== SCENARIO COMPARISON ===\n";
echo "Min Balance Difference (Baseline - Pessimistic): MYR {$balanceDifference}\n";

if (bccomp($balanceDifference, '10000', 2) > 0) {
    echo "⚠ HIGH VARIANCE: Consider securing additional credit line\n";
}

// ============================================
// Scenario 2: Manual Reconciliation with Tolerance
// ============================================

$reconciliationEngine = app(ReconciliationEngineInterface::class);

// Define custom tolerance
$tolerance = new ReconciliationTolerance(
    amountTolerance: '0.50', // MYR 0.50 variance allowed
    dateTolerance: 3 // 3 days variance allowed
);

// Reconcile with custom tolerance
$result = $reconciliationEngine->reconcileStatement(
    statementId: 'statement_xyz',
    tolerance: $tolerance
);

echo "\n=== RECONCILIATION WITH TOLERANCE ===\n";
echo "Tolerance: ±MYR {$tolerance->amountTolerance}, ±{$tolerance->dateTolerance} days\n";
echo "Matched: {$result->getMatchedCount()}\n";
echo "Variance Review: {$result->getVarianceCount()}\n";

// Review variance transactions
$varianceTransactions = $result->getVarianceTransactions();

foreach ($varianceTransactions as $transaction) {
    echo "\n  Transaction: {$transaction->getId()}\n";
    echo "  Amount Variance: MYR {$transaction->getAmountVariance()}\n";
    echo "  Date Variance: {$transaction->getDateVariance()} days\n";
    echo "  Confidence: {$transaction->getMatchingConfidence()->value}\n";
}

// ============================================
// Scenario 3: High-Value Transaction Escalation
// ============================================

$workflowEngine = app(WorkflowEngineInterface::class);
$settingManager = app(\Nexus\Setting\Contracts\SettingsManagerInterface::class);

// Get high-value threshold from settings
$highValueThreshold = $settingManager->get('cash.high_value_threshold', '10000');

// Check for high-value unmatched transactions
$unmatchedTransactions = $result->getUnmatchedTransactions();

foreach ($unmatchedTransactions as $transaction) {
    if (bccomp($transaction->getAmount(), $highValueThreshold, 4) > 0) {
        echo "\n=== HIGH-VALUE TRANSACTION ESCALATION ===\n";
        echo "Transaction ID: {$transaction->getId()}\n";
        echo "Amount: MYR {$transaction->getAmount()}\n";
        echo "Threshold: MYR {$highValueThreshold}\n";
        
        // Initiate approval workflow
        $workflowInstance = $workflowEngine->startProcess(
            'high_value_reconciliation_review',
            [
                'transaction_id' => $transaction->getId(),
                'amount' => $transaction->getAmount(),
                'approver_role' => 'senior_finance_manager',
                'escalation_level' => 1,
            ]
        );
        
        echo "✓ Workflow Initiated: {$workflowInstance->getId()}\n";
        echo "  Assigned to: senior_finance_manager\n";
    }
}

// ============================================
// Scenario 4: AI Feedback Loop
// ============================================

$intelligenceService = app(ClassificationServiceInterface::class);

// Get pending adjustment with AI suggestion
$pendingAdjustment = app(PendingAdjustmentInterface::class);
$aiSuggestedAccount = $pendingAdjustment->getSuggestedGlAccount();
$aiModelVersion = $pendingAdjustment->getAiModelVersion();

echo "\n=== AI CLASSIFICATION FEEDBACK ===\n";
echo "AI Suggested GL Account: {$aiSuggestedAccount}\n";
echo "AI Model Version: {$aiModelVersion}\n";

// User overrides suggestion
$userSelectedAccount = '6210'; // Different from AI suggestion

if ($userSelectedAccount !== $aiSuggestedAccount) {
    // Record correction for model retraining
    $intelligenceService->recordCorrection(
        modelType: 'bank_fee_categorization',
        features: [
            'description' => $pendingAdjustment->getDescription(),
            'amount' => $pendingAdjustment->getAmount(),
            'transaction_type' => 'fee',
        ],
        suggestedClass: $aiSuggestedAccount,
        correctClass: $userSelectedAccount
    );
    
    // Mark correction timestamp
    $pendingAdjustment->setCorrectionRecordedAt(new \DateTimeImmutable());
    
    echo "✓ User Override Recorded\n";
    echo "  AI Suggested: {$aiSuggestedAccount}\n";
    echo "  User Selected: {$userSelectedAccount}\n";
    echo "  Correction sent to Intelligence package for retraining\n";
}

// ============================================
// Scenario 5: Find Potential Matches
// ============================================

// Get potential matches for manual review
$bankTransactionId = 'transaction_xyz';
$potentialMatches = $reconciliationEngine->findPotentialMatches(
    bankTransactionId: $bankTransactionId,
    limit: 5
);

echo "\n=== POTENTIAL MATCHES FOR MANUAL REVIEW ===\n";
echo "Transaction ID: {$bankTransactionId}\n";
echo "Found {$potentialMatches->count()} potential matches:\n";

foreach ($potentialMatches as $index => $match) {
    echo "\n  Match #{$index + 1}:\n";
    echo "  Entity Type: {$match->getMatchedEntityType()}\n";
    echo "  Entity ID: {$match->getMatchedEntityId()}\n";
    echo "  Confidence: {$match->getMatchingConfidence()->value}\n";
    echo "  Amount Variance: MYR {$match->getAmountVariance()}\n";
}

// ============================================
// Scenario 6: Cash Position Tracking
// ============================================

$cashPosition = app(CashPositionInterface::class);

// Get balance at specific date
$balanceDate = new \DateTimeImmutable('2024-11-15');
$bankAccountId = 'bank_account_xyz';

$balanceAtDate = $cashPosition->getBalanceAt($bankAccountId, $balanceDate);

echo "\n=== CASH POSITION TRACKING ===\n";
echo "Bank Account: {$bankAccountId}\n";
echo "Date: {$balanceDate->format('Y-m-d')}\n";
echo "Balance: MYR {$balanceAtDate}\n";

// Get consolidated balance across all accounts
$consolidatedBalance = $cashPosition->getConsolidatedBalance(
    tenantId: 'tenant_abc123',
    date: $balanceDate
);

echo "\nConsolidated Cash Position (All Accounts): MYR {$consolidatedBalance}\n";

// ============================================
// Scenario 7: Multi-Currency Operations (V2)
// ============================================

use Nexus\Currency\Contracts\CurrencyManagerInterface;

$currencyManager = app(CurrencyManagerInterface::class);

// Example: Bank transaction in USD, functional currency is MYR
$transactionAmountUSD = '1000.00';
$exchangeRateUSDtoMYR = $currencyManager->getExchangeRate('USD', 'MYR');

$functionalAmount = bcmul($transactionAmountUSD, $exchangeRateUSDtoMYR, 4);

echo "\n=== MULTI-CURRENCY TRANSACTION (V2) ===\n";
echo "Transaction Currency: USD\n";
echo "Transaction Amount: USD {$transactionAmountUSD}\n";
echo "Exchange Rate (USD → MYR): {$exchangeRateUSDtoMYR}\n";
echo "Functional Amount: MYR {$functionalAmount}\n";

// Store multi-currency transaction
$bankTransaction = [
    'amount' => $functionalAmount, // Functional currency
    'transaction_currency' => 'USD',
    'exchange_rate' => $exchangeRateUSDtoMYR,
    'functional_amount' => $functionalAmount,
];

echo "✓ Transaction recorded with multi-currency metadata\n";

// ============================================
// Scenario 8: Automated Variance Analysis
// ============================================

// Calculate variance statistics
$totalVariance = '0.00';
$varianceCount = 0;

foreach ($varianceTransactions as $transaction) {
    $totalVariance = bcadd($totalVariance, $transaction->getAmountVariance(), 4);
    $varianceCount++;
}

$averageVariance = $varianceCount > 0 
    ? bcdiv($totalVariance, (string)$varianceCount, 4) 
    : '0.00';

echo "\n=== VARIANCE ANALYSIS ===\n";
echo "Total Variance Transactions: {$varianceCount}\n";
echo "Total Variance Amount: MYR {$totalVariance}\n";
echo "Average Variance: MYR {$averageVariance}\n";

// Alert if average variance exceeds threshold
$varianceThreshold = '5.00';
if (bccomp($averageVariance, $varianceThreshold, 4) > 0) {
    echo "⚠ HIGH AVERAGE VARIANCE: Review reconciliation process\n";
}

// ============================================
// Output Summary
// ============================================

echo "\n========================================\n";
echo "ADVANCED OPERATIONS SUMMARY\n";
echo "========================================\n";
echo "Forecasts Generated: 2 (Baseline, Pessimistic)\n";
echo "Liquidity Risk: " . ($baselineForecast->hasNegativeBalance() ? 'Detected' : 'None') . "\n";
echo "Reconciliation Tolerance: ±MYR {$tolerance->amountTolerance}\n";
echo "High-Value Escalations: 1\n";
echo "AI Corrections Recorded: 1\n";
echo "Potential Matches Found: {$potentialMatches->count()}\n";
echo "Average Variance: MYR {$averageVariance}\n";
echo "========================================\n";

// Expected output:
/*
=== BASELINE FORECAST (90 Days) ===
Min Balance: MYR 15000.00
Max Balance: MYR 85000.00
Has Negative: NO

=== PESSIMISTIC FORECAST (90 Days) ===
Min Balance: MYR 5000.00
Has Negative: NO

=== SCENARIO COMPARISON ===
Min Balance Difference (Baseline - Pessimistic): MYR 10000.00

=== RECONCILIATION WITH TOLERANCE ===
Tolerance: ±MYR 0.50, ±3 days
Matched: 42
Variance Review: 3

=== HIGH-VALUE TRANSACTION ESCALATION ===
Transaction ID: 01HXYZ...
Amount: MYR 25000.00
Threshold: MYR 10000.00
✓ Workflow Initiated: 01HXYZ...
  Assigned to: senior_finance_manager

=== AI CLASSIFICATION FEEDBACK ===
AI Suggested GL Account: 6200
AI Model Version: 1.2.3
✓ User Override Recorded
  AI Suggested: 6200
  User Selected: 6210
  Correction sent to Intelligence package for retraining

=== POTENTIAL MATCHES FOR MANUAL REVIEW ===
Transaction ID: transaction_xyz
Found 5 potential matches:

=== CASH POSITION TRACKING ===
Bank Account: bank_account_xyz
Date: 2024-11-15
Balance: MYR 28500.00

Consolidated Cash Position (All Accounts): MYR 125000.00

=== MULTI-CURRENCY TRANSACTION (V2) ===
Transaction Currency: USD
Transaction Amount: USD 1000.00
Exchange Rate (USD → MYR): 4.7500
Functional Amount: MYR 4750.0000
✓ Transaction recorded with multi-currency metadata

=== VARIANCE ANALYSIS ===
Total Variance Transactions: 3
Total Variance Amount: MYR 1.25
Average Variance: MYR 0.4167

========================================
ADVANCED OPERATIONS SUMMARY
========================================
Forecasts Generated: 2 (Baseline, Pessimistic)
Liquidity Risk: None
Reconciliation Tolerance: ±MYR 0.50
High-Value Escalations: 1
AI Corrections Recorded: 1
Potential Matches Found: 5
Average Variance: MYR 0.4167
========================================
*/
