<?php

declare(strict_types=1);

/**
 * Capacity Planning Example
 *
 * This example demonstrates capacity planning including
 * load analysis, bottleneck detection, and resolution suggestions.
 */

use Nexus\Manufacturing\Services\CapacityPlanner;
use Nexus\Manufacturing\Services\CapacityResolver;
use Nexus\Manufacturing\Services\WorkCenterManager;
use Nexus\Manufacturing\ValueObjects\PlanningHorizon;
use Nexus\Manufacturing\Enums\CapacityLoadType;
use Nexus\Manufacturing\Enums\ResolutionAction;

// =============================================================================
// Setup - In your application, these would be injected via DI
// =============================================================================

/** @var CapacityPlanner $capacityPlanner */
/** @var CapacityResolver $capacityResolver */
/** @var WorkCenterManager $workCenterManager */

// =============================================================================
// Example 1: Work Center Setup
// =============================================================================

echo "Example 1: Work Center Configuration\n";
echo str_repeat('-', 50) . "\n";

// Create a work center
$workCenter = $workCenterManager->create(
    code: 'WC-ASSEMBLY-01',
    name: 'Assembly Line 1',
    capacityPerHour: 10.0,      // 10 units per hour
    availableHoursPerDay: 16.0, // 2 shifts x 8 hours
    efficiency: 0.85,           // 85% efficiency
    costPerHour: 75.00
);

echo "Work Center: {$workCenter->getCode()}\n";
echo "  Name: {$workCenter->getName()}\n";
echo "  Capacity: {$workCenter->getCapacityPerHour()} units/hour\n";
echo "  Available: {$workCenter->getAvailableHoursPerDay()} hours/day\n";
echo "  Efficiency: " . ($workCenter->getEfficiency() * 100) . "%\n";
echo "  Effective Capacity: " . ($workCenter->getCapacityPerHour() * $workCenter->getAvailableHoursPerDay() * $workCenter->getEfficiency()) . " units/day\n\n";

// =============================================================================
// Example 2: Calculate Capacity Load
// =============================================================================

echo "Example 2: Capacity Load Analysis\n";
echo str_repeat('-', 50) . "\n";

$horizon = new PlanningHorizon(
    startDate: new DateTimeImmutable('today'),
    endDate: new DateTimeImmutable('+30 days'),
    bucketSizeDays: 7,
    frozenZoneDays: 7,
    slushyZoneDays: 14
);

$capacityLoad = $capacityPlanner->calculateLoad(
    workCenterId: $workCenter->getId(),
    horizon: $horizon,
    loadType: CapacityLoadType::FINITE
);

echo "Capacity load for {$workCenter->getCode()}:\n\n";
echo sprintf(
    "%-15s %10s %10s %10s %10s\n",
    'Week Starting',
    'Available',
    'Required',
    'Load %',
    'Status'
);
echo str_repeat('-', 60) . "\n";

foreach ($capacityLoad->getBuckets() as $bucket) {
    $loadPercent = ($bucket->getRequired() / $bucket->getAvailable()) * 100;
    $status = match (true) {
        $loadPercent > 100 => '🔴 Over',
        $loadPercent > 85 => '🟡 High',
        $loadPercent > 60 => '🟢 Normal',
        default => '⚪ Low',
    };
    
    echo sprintf(
        "%-15s %10.1f %10.1f %9.1f%% %10s\n",
        $bucket->getStartDate()->format('Y-m-d'),
        $bucket->getAvailable(),
        $bucket->getRequired(),
        $loadPercent,
        $status
    );
}

echo "\n";

// =============================================================================
// Example 3: Detect Bottlenecks
// =============================================================================

echo "Example 3: Bottleneck Detection\n";
echo str_repeat('-', 50) . "\n";

$bottlenecks = $capacityPlanner->detectBottlenecks($horizon);

if (empty($bottlenecks)) {
    echo "No bottlenecks detected in planning horizon.\n\n";
} else {
    echo "Bottlenecks detected:\n\n";
    
    foreach ($bottlenecks as $bottleneck) {
        echo "🔴 {$bottleneck->getWorkCenterName()}\n";
        echo "   Period: {$bottleneck->getStartDate()->format('Y-m-d')} to {$bottleneck->getEndDate()->format('Y-m-d')}\n";
        echo "   Overload: {$bottleneck->getOverloadHours()} hours ({$bottleneck->getOverloadPercent()}%)\n";
        echo "   Affected Orders: " . count($bottleneck->getAffectedOrders()) . "\n\n";
    }
}

// =============================================================================
// Example 4: Resolution Suggestions
// =============================================================================

echo "Example 4: Resolution Suggestions\n";
echo str_repeat('-', 50) . "\n";

if (!empty($bottlenecks)) {
    $bottleneck = $bottlenecks[0];
    
    $suggestions = $capacityResolver->getSuggestions(
        $bottleneck->getWorkCenterId(),
        $bottleneck->getStartDate(),
        $bottleneck->getEndDate()
    );
    
    echo "Resolution suggestions for {$bottleneck->getWorkCenterName()}:\n\n";
    
    foreach ($suggestions as $index => $suggestion) {
        $priority = $index + 1;
        echo "{$priority}. {$suggestion->action->value}\n";
        echo "   Description: {$suggestion->description}\n";
        echo "   Impact: Reduces load by {$suggestion->impactHours} hours\n";
        
        if ($suggestion->estimatedCost !== null) {
            echo "   Estimated Cost: \${$suggestion->estimatedCost}\n";
        }
        
        if (!empty($suggestion->affectedOrders)) {
            echo "   Affected Orders: " . implode(', ', array_slice($suggestion->affectedOrders, 0, 3));
            if (count($suggestion->affectedOrders) > 3) {
                echo " +" . (count($suggestion->affectedOrders) - 3) . " more";
            }
            echo "\n";
        }
        
        echo "\n";
    }
}

// =============================================================================
// Example 5: Apply Resolution
// =============================================================================

echo "Example 5: Applying Resolution\n";
echo str_repeat('-', 50) . "\n";

if (!empty($suggestions)) {
    $selectedSuggestion = $suggestions[0];
    
    echo "Applying suggestion: {$selectedSuggestion->action->value}\n\n";
    
    $result = $capacityResolver->applyResolution($selectedSuggestion);
    
    if ($result->isSuccessful()) {
        echo "✅ Resolution applied successfully!\n";
        echo "   Actions taken:\n";
        
        foreach ($result->getActionsTaken() as $action) {
            echo "   - {$action}\n";
        }
        
        echo "\n   New load summary:\n";
        $newLoad = $result->getNewLoadPercent();
        echo "   Load: {$newLoad}%\n";
    } else {
        echo "❌ Resolution failed: {$result->getErrorMessage()}\n";
    }
} else {
    echo "No resolutions to apply (no bottlenecks).\n";
}

echo "\n";

// =============================================================================
// Example 6: What-If Analysis
// =============================================================================

echo "Example 6: What-If Analysis\n";
echo str_repeat('-', 50) . "\n";

// Simulate adding a new work order
$whatIfResult = $capacityPlanner->whatIf(
    workCenterId: $workCenter->getId(),
    additionalHours: 40.0,
    targetDate: new DateTimeImmutable('+10 days')
);

echo "What-if: Adding 40 hours of work to {$workCenter->getCode()}\n";
echo "Target date: " . (new DateTimeImmutable('+10 days'))->format('Y-m-d') . "\n\n";

echo "Impact Analysis:\n";
echo "  Current Load: {$whatIfResult->getCurrentLoadPercent()}%\n";
echo "  Projected Load: {$whatIfResult->getProjectedLoadPercent()}%\n";
echo "  Would Create Bottleneck: " . ($whatIfResult->wouldCreateBottleneck() ? 'Yes' : 'No') . "\n";

if ($whatIfResult->wouldCreateBottleneck()) {
    echo "  Suggested Alternative Date: {$whatIfResult->getSuggestedDate()->format('Y-m-d')}\n";
}

echo "\n";

// =============================================================================
// Example 7: Finite vs Infinite Capacity
// =============================================================================

echo "Example 7: Finite vs Infinite Capacity Planning\n";
echo str_repeat('-', 50) . "\n";

// Infinite capacity (no constraints)
$infiniteLoad = $capacityPlanner->calculateLoad(
    workCenterId: $workCenter->getId(),
    horizon: $horizon,
    loadType: CapacityLoadType::INFINITE
);

echo "Infinite Capacity Planning:\n";
echo "  Assumes unlimited capacity\n";
echo "  Shows true demand without constraints\n";
echo "  Total Required: {$infiniteLoad->getTotalRequired()} hours\n\n";

// Finite capacity (respects constraints)
$finiteLoad = $capacityPlanner->calculateLoad(
    workCenterId: $workCenter->getId(),
    horizon: $horizon,
    loadType: CapacityLoadType::FINITE
);

echo "Finite Capacity Planning:\n";
echo "  Respects capacity constraints\n";
echo "  May push orders to future periods\n";
echo "  Total Available: {$finiteLoad->getTotalAvailable()} hours\n";
echo "  Total Scheduled: {$finiteLoad->getTotalScheduled()} hours\n\n";

// =============================================================================
// Example 8: Multi-Work Center Analysis
// =============================================================================

echo "Example 8: Multi-Work Center Summary\n";
echo str_repeat('-', 50) . "\n";

$allWorkCenters = $workCenterManager->getAll();
$plantSummary = $capacityPlanner->getPlantCapacitySummary($horizon);

echo sprintf(
    "%-20s %10s %10s %10s %10s\n",
    'Work Center',
    'Available',
    'Scheduled',
    'Load %',
    'Status'
);
echo str_repeat('-', 65) . "\n";

foreach ($plantSummary as $wcSummary) {
    echo sprintf(
        "%-20s %10.1f %10.1f %9.1f%% %10s\n",
        $wcSummary->getWorkCenterName(),
        $wcSummary->getTotalAvailable(),
        $wcSummary->getTotalScheduled(),
        $wcSummary->getAverageLoad(),
        $wcSummary->getStatus()
    );
}

echo "\n";
echo "Overall Plant Utilization: {$plantSummary->getOverallUtilization()}%\n";
echo "Bottleneck Work Centers: " . count($plantSummary->getBottlenecks()) . "\n";

echo "\n";
echo "Capacity planning example complete!\n";
