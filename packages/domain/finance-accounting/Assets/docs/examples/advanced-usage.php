<?php

/**
 * Advanced Usage Example: Nexus\Assets
 * 
 * This example demonstrates Tier 2 and Tier 3 features:
 * - Double Declining Balance depreciation (Tier 2)
 * - Maintenance tracking and TCO analysis (Tier 2)
 * - Units of Production depreciation (Tier 3)
 * - Physical asset audits (Tier 3)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Nexus\Assets\Contracts\AssetManagerInterface;
use Nexus\Assets\Contracts\MaintenanceAnalyzerInterface;
use Nexus\Assets\Contracts\AssetVerifierInterface;
use Nexus\Assets\Enums\DepreciationMethod;
use Nexus\Assets\Enums\MaintenanceType;

function tier2AdvancedFeatures(
    AssetManagerInterface $assetManager,
    MaintenanceAnalyzerInterface $maintenanceAnalyzer
): void {
    echo "=== Tier 2 (Advanced) Features ===\n\n";

    // ===================================================================
    // 1. Create Asset with Double Declining Balance + Warranty
    // ===================================================================
    
    echo "1. Creating company vehicle with DDB depreciation...\n";
    
    $vehicle = $assetManager->createAsset([
        'name' => 'Toyota Camry 2025',
        'description' => 'Company vehicle for sales team',
        'category_id' => 'vehicles',
        'cost' => 45000.00,
        'salvage_value' => 10000.00,
        'acquisition_date' => new \DateTimeImmutable('2025-01-01'),
        'depreciation_method' => DepreciationMethod::DOUBLE_DECLINING_BALANCE,
        'useful_life_months' => 60, // 5 years
        'location' => 'Parking Lot A',
    ])->withWarranty(
        provider: 'Toyota Motor Corporation',
        startDate: new \DateTimeImmutable('2025-01-01'),
        expiryDate: new \DateTimeImmutable('2028-01-01'),
        coverageType: 'Comprehensive warranty - bumper to bumper'
    );

    echo "   ✓ Vehicle created with warranty!\n";
    echo "   - Asset Tag: {$vehicle->getAssetTag()}\n";
    echo "   - Depreciation Method: Double Declining Balance\n";
    echo "   - Warranty Provider: Toyota Motor Corporation\n\n";

    // ===================================================================
    // 2. Record Maintenance Activities
    // ===================================================================
    
    echo "2. Recording maintenance activities...\n";
    
    // Routine maintenance
    $assetManager->withMaintenance(
        assetId: $vehicle->getId(),
        type: MaintenanceType::ROUTINE,
        description: 'Oil change and tire rotation',
        cost: 120.00,
        performedDate: new \DateTimeImmutable('2025-03-15')
    );

    $assetManager->withMaintenance(
        assetId: $vehicle->getId(),
        type: MaintenanceType::PREVENTIVE,
        description: 'Brake pad replacement',
        cost: 450.00,
        performedDate: new \DateTimeImmutable('2025-06-10')
    );

    $assetManager->withMaintenance(
        assetId: $vehicle->getId(),
        type: MaintenanceType::CORRECTIVE,
        description: 'Air conditioning repair',
        cost: 850.00,
        performedDate: new \DateTimeImmutable('2025-08-22')
    );

    echo "   ✓ 3 maintenance records added\n\n";

    // ===================================================================
    // 3. Calculate Total Cost of Ownership (TCO)
    // ===================================================================
    
    echo "3. Calculating Total Cost of Ownership (5 years)...\n";
    
    $tco = $maintenanceAnalyzer->calculateTCO(
        assetId: $vehicle->getId(),
        projectedYears: 5
    );

    echo "   ✓ TCO Analysis:\n";
    echo "   - Acquisition Cost: \${$tco['acquisition_cost']}\n";
    echo "   - Historical Maintenance: \${$tco['maintenance_cost']}\n";
    echo "   - Projected Maintenance (5 years): \${$tco['projected_maintenance']}\n";
    echo "   - TOTAL COST OF OWNERSHIP: \${$tco['total']}\n\n";

    // ===================================================================
    // 4. Analyze Maintenance Patterns
    // ===================================================================
    
    echo "4. Analyzing maintenance patterns...\n";
    
    $pattern = $maintenanceAnalyzer->analyzeMaintenancePattern($vehicle->getId());

    echo "   ✓ Maintenance Pattern Analysis:\n";
    echo "   - Total Maintenance Events: {$pattern['total_maintenance']}\n";
    echo "   - Average Cost per Event: \${$pattern['avg_cost']}\n";
    echo "   - Preventive Ratio: {$pattern['preventive_ratio']}%\n";
    echo "   - Corrective Ratio: {$pattern['corrective_ratio']}%\n\n";

    // ===================================================================
    // 5. Predict Next Maintenance
    // ===================================================================
    
    echo "5. Predicting next maintenance date...\n";
    
    $prediction = $maintenanceAnalyzer->predictNextMaintenance($vehicle->getId());

    echo "   ✓ Maintenance Prediction:\n";
    echo "   - Next Recommended Date: {$prediction['predicted_date']->format('Y-m-d')}\n";
    echo "   - Based on Avg Interval: {$prediction['average_interval_days']} days\n";
    echo "   - Last Maintenance: {$prediction['last_maintenance_date']->format('Y-m-d')}\n\n";
}

function tier3EnterpriseFeatures(
    AssetManagerInterface $assetManager,
    AssetVerifierInterface $assetVerifier
): void {
    echo "=== Tier 3 (Enterprise) Features ===\n\n";

    // ===================================================================
    // 1. Create Asset with Units of Production Depreciation
    // ===================================================================
    
    echo "1. Creating industrial printer with UOP depreciation...\n";
    
    $printer = $assetManager->createAsset([
        'name' => 'Industrial Label Printer Pro',
        'description' => 'High-volume industrial printer',
        'category_id' => 'machinery',
        'cost' => 75000.00,
        'salvage_value' => 5000.00,
        'acquisition_date' => new \DateTimeImmutable('2025-01-01'),
        'depreciation_method' => DepreciationMethod::UNITS_OF_PRODUCTION,
        'useful_life_months' => 60,
        'total_expected_units' => 2000000, // 2 million prints
        'unit_type' => 'prints',
        'location_id' => 'WAREHOUSE-A',
        'currency_code' => 'MYR',
    ]);

    $depreciationRate = ($printer->getCost() - $printer->getSalvageValue()) / $printer->getTotalExpectedUnits();

    echo "   ✓ Printer created with UOP depreciation!\n";
    echo "   - Asset Tag: {$printer->getAssetTag()}\n";
    echo "   - Total Expected Units: {$printer->getTotalExpectedUnits()} prints\n";
    echo "   - Depreciation Rate: $" . number_format($depreciationRate, 4) . " per print\n\n";

    // ===================================================================
    // 2. Record Usage-Based Depreciation
    // ===================================================================
    
    echo "2. Recording depreciation based on actual usage...\n";
    
    $unitsConsumedJan = 85000; // January: 85,000 prints
    $recordJan = $assetManager->recordDepreciation(
        id: $printer->getId(),
        periodStart: new \DateTimeImmutable('2025-01-01'),
        periodEnd: new \DateTimeImmutable('2025-01-31'),
        unitsConsumed: $unitsConsumedJan
    );

    echo "   ✓ January Depreciation:\n";
    echo "   - Units Consumed: " . number_format($unitsConsumedJan) . " prints\n";
    echo "   - Depreciation Amount: \${$recordJan->getAmount()}\n";
    echo "   - Net Book Value: \${$recordJan->getNetBookValue()}\n\n";

    $unitsConsumedFeb = 120000; // February: 120,000 prints
    $recordFeb = $assetManager->recordDepreciation(
        id: $printer->getId(),
        periodStart: new \DateTimeImmutable('2025-02-01'),
        periodEnd: new \DateTimeImmutable('2025-02-28'),
        unitsConsumed: $unitsConsumedFeb
    );

    echo "   ✓ February Depreciation:\n";
    echo "   - Units Consumed: " . number_format($unitsConsumedFeb) . " prints\n";
    echo "   - Depreciation Amount: \${$recordFeb->getAmount()}\n";
    echo "   - Cumulative Depreciation: \${$recordFeb->getAccumulatedDepreciation()}\n\n";

    // ===================================================================
    // 3. Conduct Physical Asset Audit
    // ===================================================================
    
    echo "3. Initiating physical asset audit...\n";
    
    $audit = $assetVerifier->initiatePhysicalAudit([
        'location_ids' => ['WAREHOUSE-A', 'WAREHOUSE-B'],
        'scheduled_date' => new \DateTimeImmutable('2025-12-31'),
    ]);

    echo "   ✓ Audit initiated!\n";
    echo "   - Audit ID: {$audit->getId()}\n";
    echo "   - Locations: WAREHOUSE-A, WAREHOUSE-B\n";
    echo "   - Assets to Verify: {$audit->getTotalAssetsExpected()}\n\n";

    // ===================================================================
    // 4. Record Physical Verifications
    // ===================================================================
    
    echo "4. Recording physical verifications...\n";
    
    $assetVerifier->recordPhysicalVerification(
        auditId: $audit->getId(),
        assetTag: $printer->getAssetTag(),
        condition: 'Good - operational',
        actualLocation: 'WAREHOUSE-A',
        notes: 'Asset found in expected location, no issues'
    );

    echo "   ✓ Verified: {$printer->getAssetTag()}\n";
    
    // Verify additional assets (simulated)
    $assetVerifier->recordPhysicalVerification(
        auditId: $audit->getId(),
        assetTag: 'AST-000123',
        condition: 'Fair - minor wear',
        actualLocation: 'WAREHOUSE-A'
    );
    
    $assetVerifier->recordPhysicalVerification(
        auditId: $audit->getId(),
        assetTag: 'AST-000124',
        condition: 'Excellent',
        actualLocation: 'WAREHOUSE-B'
    );

    echo "   ✓ Additional assets verified\n\n";

    // ===================================================================
    // 5. Complete Audit and Review Results
    // ===================================================================
    
    echo "5. Completing physical audit...\n";
    
    $results = $assetVerifier->completePhysicalAudit($audit->getId());

    echo "   ✓ Audit completed!\n";
    echo "   - Assets Verified: {$results['verified_count']}\n";
    echo "   - Missing Assets: {$results['missing_count']}\n";
    echo "   - Extra Assets Found: {$results['extra_count']}\n";
    echo "   - Location Mismatches: {$results['location_mismatches']}\n";
    echo "   - Accuracy Rate: {$results['accuracy_rate']}%\n\n";

    if ($results['accuracy_rate'] >= 95.0) {
        echo "   ✅ Audit passed! Accuracy meets threshold (95%)\n\n";
    } else {
        echo "   ⚠️  Audit below threshold! Investigation required.\n\n";
    }
}

// Example usage (uncomment when integrated):
// $assetManager = $container->get(AssetManagerInterface::class);
// $maintenanceAnalyzer = $container->get(MaintenanceAnalyzerInterface::class);
// $assetVerifier = $container->get(AssetVerifierInterface::class);
//
// echo "=== Nexus\Assets - Advanced Usage Example ===\n\n";
// tier2AdvancedFeatures($assetManager, $maintenanceAnalyzer);
// tier3EnterpriseFeatures($assetManager, $assetVerifier);
//
// echo "=== Example Complete ===\n";
// echo "For basic features (Tier 1), see basic-usage.php\n";
