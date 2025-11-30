<?php

/**
 * Basic Usage Example: Nexus\Assets
 * 
 * This example demonstrates Tier 1 (Basic) features:
 * - Creating fixed assets
 * - Recording straight-line depreciation
 * - Disposing assets
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Nexus\Assets\Contracts\AssetManagerInterface;
use Nexus\Assets\Enums\DepreciationMethod;
use Nexus\Assets\Enums\DisposalMethod;

// Assuming AssetManagerInterface is bound in your DI container
// For demonstration, we'll use a hypothetical $assetManager instance

function basicAssetTracking(AssetManagerInterface $assetManager): void
{
    echo "=== Nexus\Assets - Basic Usage Example ===\n\n";

    // ===================================================================
    // 1. Create a Fixed Asset (Office Desk)
    // ===================================================================
    
    echo "1. Creating Office Desk asset...\n";
    
    $asset = $assetManager->createAsset([
        'name' => 'Executive Office Desk',
        'description' => 'Solid wood executive desk with drawers',
        'category_id' => 'furniture',
        'cost' => 1200.00,
        'salvage_value' => 100.00,
        'acquisition_date' => new \DateTimeImmutable('2025-01-15'),
        'depreciation_method' => DepreciationMethod::STRAIGHT_LINE,
        'useful_life_months' => 60, // 5 years
        'location' => 'HQ Office, Floor 3, Room 301',
    ]);

    echo "   ✓ Asset created successfully!\n";
    echo "   - Asset Tag: {$asset->getAssetTag()}\n";
    echo "   - Cost: \${$asset->getCost()}\n";
    echo "   - Salvage Value: \${$asset->getSalvageValue()}\n";
    echo "   - Useful Life: {$asset->getUsefulLifeMonths()} months\n";
    echo "   - Depreciation per month: $" . number_format(($asset->getCost() - $asset->getSalvageValue()) / $asset->getUsefulLifeMonths(), 2) . "\n\n";

    // ===================================================================
    // 2. Record Monthly Depreciation (January 2025)
    // ===================================================================
    
    echo "2. Recording depreciation for January 2025...\n";
    
    $depreciationRecord = $assetManager->recordDepreciation(
        id: $asset->getId(),
        periodStart: new \DateTimeImmutable('2025-01-01'),
        periodEnd: new \DateTimeImmutable('2025-01-31')
    );

    echo "   ✓ Depreciation recorded!\n";
    echo "   - Period: January 2025\n";
    echo "   - Depreciation Amount: \${$depreciationRecord->getAmount()}\n";
    echo "   - Accumulated Depreciation: \${$depreciationRecord->getAccumulatedDepreciation()}\n";
    echo "   - Net Book Value: \${$depreciationRecord->getNetBookValue()}\n\n";

    // ===================================================================
    // 3. Record Depreciation for Multiple Months
    // ===================================================================
    
    echo "3. Fast-forward: Recording depreciation for 24 months...\n";
    
    $startDate = new \DateTimeImmutable('2025-02-01');
    for ($i = 0; $i < 24; $i++) {
        $periodStart = $startDate->modify("+{$i} month");
        $periodEnd = $periodStart->modify('last day of this month');
        
        $assetManager->recordDepreciation(
            id: $asset->getId(),
            periodStart: $periodStart,
            periodEnd: $periodEnd
        );
    }

    // Fetch updated asset
    $updatedAsset = $assetManager->getAsset($asset->getId());
    
    echo "   ✓ 24 months of depreciation recorded!\n";
    echo "   - Total Accumulated Depreciation: \${$updatedAsset->getAccumulatedDepreciation()}\n";
    echo "   - Current Net Book Value: $" . number_format($updatedAsset->getCost() - $updatedAsset->getAccumulatedDepreciation(), 2) . "\n\n";

    // ===================================================================
    // 4. Dispose of Asset (Sale)
    // ===================================================================
    
    echo "4. Disposing asset (sale)...\n";
    
    $disposal = $assetManager->disposeAsset(
        id: $asset->getId(),
        method: DisposalMethod::SALE,
        disposalDate: new \DateTimeImmutable('2027-01-31'),
        proceeds: 650.00,
        notes: 'Sold to employee at market value'
    );

    echo "   ✓ Asset disposed successfully!\n";
    echo "   - Disposal Method: Sale\n";
    echo "   - Proceeds: \${$disposal['proceeds']}\n";
    echo "   - Final Net Book Value: \${$disposal['final_nbv']}\n";
    echo "   - Gain/Loss on Disposal: \${$disposal['gain_loss']} ";
    echo $disposal['gain_loss'] >= 0 ? "(Gain)\n" : "(Loss)\n";
    echo "\n";

    // ===================================================================
    // Summary
    // ===================================================================
    
    echo "=== Summary ===\n";
    echo "This example demonstrated Tier 1 (Basic) features:\n";
    echo "✓ Asset creation with straight-line depreciation\n";
    echo "✓ Monthly depreciation recording\n";
    echo "✓ Asset disposal with gain/loss calculation\n";
    echo "\nFor advanced features (Tier 2/3), see advanced-usage.php\n";
}

// Example usage (uncomment when integrated):
// $assetManager = $container->get(AssetManagerInterface::class);
// basicAssetTracking($assetManager);
