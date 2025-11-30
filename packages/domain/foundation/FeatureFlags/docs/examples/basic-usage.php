<?php

declare(strict_types=1);

/**
 * Basic Usage Examples: FeatureFlags
 */

use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;
use Nexus\FeatureFlags\Enums\FlagStrategy;

// Example 1: Simple Boolean Toggle
$context = new EvaluationContext(
    tenantId: 'tenant-001',
    userId: 'user-123'
);

$isEnabled = $manager->evaluate('feature.new_ui', $context);

if ($isEnabled) {
    echo "New UI enabled\n";
}

// Example 2: Percentage Rollout
// 25% of users get new feature
$manager->evaluate('feature.beta', $context);

// Example 3: Tenant-Specific
if ($manager->evaluate('feature.premium', $context)) {
    echo "Premium feature available\n";
}

// Example 4: Bulk Evaluation
$flags = $manager->evaluateBulk([
    'feature.export',
    'feature.import',
    'feature.analytics',
], $context);

foreach ($flags as $flag => $enabled) {
    echo "{$flag}: " . ($enabled ? 'ON' : 'OFF') . "\n";
}

// Example 5: Kill Switch
// Emergency disable via admin panel
FeatureFlag::where('key', 'feature.buggy')
    ->update(['override' => 'force_off']);
