<?php

declare(strict_types=1);

/**
 * Basic Usage Example - Bill of Materials Management
 *
 * This example demonstrates basic BOM operations including
 * creating, versioning, and exploding BOMs.
 */

use Nexus\Manufacturing\Services\BomManager;
use Nexus\Manufacturing\Contracts\BomRepositoryInterface;

// =============================================================================
// Setup - In your application, these would be injected via DI
// =============================================================================

/** @var BomRepositoryInterface $bomRepository */
/** @var BomManager $bomManager */
$bomManager = new BomManager($bomRepository);

// =============================================================================
// Example 1: Create a Manufacturing BOM
// =============================================================================

echo "Example 1: Creating a Manufacturing BOM\n";
echo str_repeat('-', 50) . "\n";

$bom = $bomManager->create(
    productId: 'WIDGET-001',
    version: '1.0',
    type: 'manufacturing',
    name: 'Widget Assembly BOM'
);

echo "Created BOM: {$bom->getId()}\n";
echo "Version: {$bom->getVersion()}\n";
echo "Type: {$bom->getType()}\n\n";

// =============================================================================
// Example 2: Add Components (Lines) to the BOM
// =============================================================================

echo "Example 2: Adding Components to BOM\n";
echo str_repeat('-', 50) . "\n";

// Add housing component
$bomManager->addLine(
    bomId: $bom->getId(),
    componentProductId: 'HOUSING-001',
    quantity: 1.0,
    uom: 'EA',
    position: 10,
    scrapPercentage: 2.5
);

// Add screws
$bomManager->addLine(
    bomId: $bom->getId(),
    componentProductId: 'SCREW-M3X10',
    quantity: 4.0,
    uom: 'EA',
    position: 20,
    scrapPercentage: 5.0
);

// Add PCB assembly (a sub-assembly with its own BOM)
$bomManager->addLine(
    bomId: $bom->getId(),
    componentProductId: 'PCB-ASSY-001',
    quantity: 1.0,
    uom: 'EA',
    position: 30,
    scrapPercentage: 1.0
);

$bom = $bomManager->getById($bom->getId());
echo "BOM now has " . count($bom->getLines()) . " lines\n\n";

// =============================================================================
// Example 3: Set Effectivity Dates (Engineering Change Control)
// =============================================================================

echo "Example 3: Setting Effectivity Dates\n";
echo str_repeat('-', 50) . "\n";

$effectiveFrom = new DateTimeImmutable('2024-01-01');
$effectiveTo = new DateTimeImmutable('2024-12-31');

$bomManager->setEffectivity(
    bomId: $bom->getId(),
    effectiveFrom: $effectiveFrom,
    effectiveTo: $effectiveTo
);

echo "BOM effective from: {$effectiveFrom->format('Y-m-d')}\n";
echo "BOM effective to: {$effectiveTo->format('Y-m-d')}\n\n";

// =============================================================================
// Example 4: Release the BOM for Production
// =============================================================================

echo "Example 4: Releasing BOM\n";
echo str_repeat('-', 50) . "\n";

$bomManager->release($bom->getId());

$bom = $bomManager->getById($bom->getId());
echo "BOM status: {$bom->getStatus()}\n\n";

// =============================================================================
// Example 5: Create a New Version (Engineering Change)
// =============================================================================

echo "Example 5: Creating New Version\n";
echo str_repeat('-', 50) . "\n";

$newBom = $bomManager->createNewVersion(
    sourceBomId: $bom->getId(),
    newVersion: '1.1'
);

echo "New BOM version: {$newBom->getVersion()}\n";
echo "New BOM ID: {$newBom->getId()}\n";
echo "Copied from: {$bom->getId()}\n\n";

// Modify the new version (e.g., add new component)
$bomManager->addLine(
    bomId: $newBom->getId(),
    componentProductId: 'WASHER-M3',
    quantity: 4.0,
    uom: 'EA',
    position: 25
);

echo "Added new component to version 1.1\n\n";

// =============================================================================
// Example 6: Explode BOM (Get All Materials Needed)
// =============================================================================

echo "Example 6: BOM Explosion\n";
echo str_repeat('-', 50) . "\n";

$orderQuantity = 100.0;
$materials = $bomManager->explode($bom->getId(), $orderQuantity);

echo "Materials needed to produce {$orderQuantity} units:\n\n";
echo sprintf("%-20s %10s %5s %10s\n", 'Component', 'Quantity', 'UOM', 'With Scrap');
echo str_repeat('-', 50) . "\n";

foreach ($materials as $material) {
    $quantityWithScrap = $material['quantity'] * (1 + ($material['scrapPercentage'] / 100));
    echo sprintf(
        "%-20s %10.2f %5s %10.2f\n",
        $material['productId'],
        $material['quantity'],
        $material['uom'],
        $quantityWithScrap
    );
}

echo "\n";

// =============================================================================
// Example 7: Find Effective BOM for a Date
// =============================================================================

echo "Example 7: Finding Effective BOM\n";
echo str_repeat('-', 50) . "\n";

$queryDate = new DateTimeImmutable('2024-06-15');
$effectiveBom = $bomManager->findEffectiveBom('WIDGET-001', $queryDate);

if ($effectiveBom) {
    echo "Effective BOM for {$queryDate->format('Y-m-d')}: {$effectiveBom->getId()}\n";
    echo "Version: {$effectiveBom->getVersion()}\n";
} else {
    echo "No effective BOM found for {$queryDate->format('Y-m-d')}\n";
}

echo "\n";

// =============================================================================
// Example 8: List All Versions
// =============================================================================

echo "Example 8: Listing All BOM Versions\n";
echo str_repeat('-', 50) . "\n";

$allVersions = $bomManager->getAllVersions('WIDGET-001');

echo "All versions for WIDGET-001:\n\n";
echo sprintf("%-15s %-10s %-15s %-15s\n", 'Version', 'Status', 'Effective From', 'Effective To');
echo str_repeat('-', 60) . "\n";

foreach ($allVersions as $version) {
    echo sprintf(
        "%-15s %-10s %-15s %-15s\n",
        $version->getVersion(),
        $version->getStatus(),
        $version->getEffectiveFrom()?->format('Y-m-d') ?? 'N/A',
        $version->getEffectiveTo()?->format('Y-m-d') ?? 'N/A'
    );
}

echo "\n";

// =============================================================================
// Example 9: Obsolete an Old BOM
// =============================================================================

echo "Example 9: Obsoleting Old BOM\n";
echo str_repeat('-', 50) . "\n";

// First release the new version
$bomManager->release($newBom->getId());

// Then obsolete the old one
$bomManager->obsolete($bom->getId());

$bom = $bomManager->getById($bom->getId());
echo "Old BOM status: {$bom->getStatus()}\n";

echo "\n";
echo "Basic BOM operations complete!\n";
