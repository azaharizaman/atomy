<?php

declare(strict_types=1);

/**
 * Advanced Usage Example: Budget
 * 
 * This example demonstrates:
 * 1. Hierarchical budget creation
 * 2. Budget transfers between accounts
 * 3. Budget simulation (what-if analysis)
 * 4. AI-powered forecasting
 * 5. Utilization alerts and monitoring
 */

use Nexus\Budget\Contracts\BudgetManagerInterface;
use Nexus\Budget\Contracts\BudgetSimulatorInterface;
use Nexus\Budget\Contracts\BudgetForecastInterface;
use Nexus\Budget\Enums\BudgetType;
use Nexus\Budget\Enums\RolloverPolicy;

// Inject services
$budgetManager = app(BudgetManagerInterface::class);
$simulator = app(BudgetSimulatorInterface::class);
$forecaster = app(BudgetForecastInterface::class);

// ============================================
// 1. Hierarchical Budget Creation
// ============================================

// Create parent budget (department level)
$parentBudget = $budgetManager->createBudget(
    tenantId: 'tenant-abc123',
    periodId: 'FY2025',
    departmentId: 'dept-IT',
    accountId: 'acc-it-opex',
    budgetType: BudgetType::Departmental,
    allocatedAmount: 500000.00,
    baseCurrency: 'MYR',
    presentationCurrency: 'USD',
    exchangeRate: 4.50
);

// Create child budgets (account level)
$softwareBudget = $budgetManager->createBudget(
    tenantId: 'tenant-abc123',
    periodId: 'FY2025',
    departmentId: 'dept-IT',
    accountId: 'acc-software',
    budgetType: BudgetType::OperatingExpense,
    allocatedAmount: 200000.00,
    baseCurrency: 'MYR',
    presentationCurrency: 'USD',
    exchangeRate: 4.50,
    parentBudgetId: $parentBudget->getId()
);

$hardwareBudget = $budgetManager->createBudget(
    tenantId: 'tenant-abc123',
    periodId: 'FY2025',
    departmentId: 'dept-IT',
    accountId: 'acc-hardware',
    budgetType: BudgetType::CapitalExpenditure,
    allocatedAmount: 300000.00,
    baseCurrency: 'MYR',
    presentationCurrency: 'USD',
    exchangeRate: 4.50,
    parentBudgetId: $parentBudget->getId()
);

echo "Created hierarchical budget structure\n";

// ============================================
// 2. Budget Transfer Between Accounts
// ============================================

// Transfer 50,000 from hardware to software budget
$budgetManager->transferAllocation(
    fromBudgetId: $hardwareBudget->getId(),
    toBudgetId: $softwareBudget->getId(),
    amount: 50000.00,
    reason: 'Cloud migration reduces hardware needs, increases software requirements',
    approvedBy: 'user-cfo-123'
);

echo "Transferred 50,000 from Hardware to Software budget\n";

// ============================================
// 3. Budget Simulation (What-If Analysis)
// ============================================

// Create a simulation scenario
$scenario = $simulator->createScenario(
    baseBudgetId: $softwareBudget->getId(),
    scenarioName: 'Cloud Migration Impact',
    description: 'Simulate moving to cloud-based infrastructure'
);

// Simulate increased software costs
$simulator->applyChange(
    scenarioId: $scenario->getId(),
    changeType: 'allocation_increase',
    amount: 100000.00,
    reason: 'Additional SaaS subscriptions'
);

// Compare scenarios
$comparison = $simulator->compareScenarios(
    baseScenarioId: $softwareBudget->getId(),
    comparisonScenarioIds: [$scenario->getId()]
);

echo "Simulation shows {$comparison->getDifference()}% variance\n";

// ============================================
// 4. AI-Powered Forecasting
// ============================================

// Generate forecast for next quarter
$forecast = $forecaster->generateForecast(
    budgetId: $softwareBudget->getId(),
    forecastPeriods: 4, // Next 4 quarters
    confidenceLevel: 0.95
);

echo "\n=== Budget Forecast ===\n";
echo "Predicted expenditure: {$forecast->getPredictedAmount()}\n";
echo "Confidence interval: [{$forecast->getLowerBound()}, {$forecast->getUpperBound()}]\n";
echo "Forecast accuracy (historical): {$forecast->getAccuracy()}%\n";

// ============================================
// 5. Utilization Alerts
// ============================================

// Check for utilization alerts
$alerts = $budgetManager->getUtilizationAlerts($softwareBudget->getId());

foreach ($alerts as $alert) {
    echo "\nAlert: {$alert->getMessage()}\n";
    echo "Severity: {$alert->getSeverity()->name}\n";
    echo "Utilization: {$alert->getUtilizationPercentage()}%\n";
    echo "Recommended actions:\n";
    foreach ($alert->getRecommendations() as $recommendation) {
        echo "  - {$recommendation}\n";
    }
}

// ============================================
// 6. Budget Rollover Configuration
// ============================================

// Configure rollover policy for unused funds
$budgetManager->setRolloverPolicy(
    budgetId: $softwareBudget->getId(),
    policy: RolloverPolicy::RequireApproval,
    maxRolloverPercentage: 20.0
);

echo "\nRollover policy set: Unused funds up to 20% require CFO approval\n";

// Expected output:
// Created hierarchical budget structure
// Transferred 50,000 from Hardware to Software budget
// Simulation shows 40.0% variance
//
// === Budget Forecast ===
// Predicted expenditure: 245000.00
// Confidence interval: [230000.00, 260000.00]
// Forecast accuracy (historical): 92.5%
//
// Alert: Budget utilization approaching 75% threshold
// Severity: Medium
// Utilization: 74.5%
// Recommended actions:
//   - Review upcoming commitments
//   - Consider budget reallocation
//   - Prepare variance investigation
//
// Rollover policy set: Unused funds up to 20% require CFO approval
