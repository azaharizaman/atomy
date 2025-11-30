<?php

declare(strict_types=1);

/**
 * Advanced Usage Examples: FeatureFlags
 */

use Nexus\FeatureFlags\Contracts\CustomEvaluatorInterface;
use Nexus\FeatureFlags\ValueObjects\FlagDefinition;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;

// Example 1: Custom Evaluator
class PremiumSubscriptionEvaluator implements CustomEvaluatorInterface
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptions
    ) {}
    
    public function evaluate(FlagDefinition $flag, EvaluationContext $context): bool
    {
        if (!$context->tenantId) {
            return false;
        }
        
        $subscription = $this->subscriptions->findByTenant($context->tenantId);
        
        return $subscription && $subscription->isPremium();
    }
}

// Example 2: Percentage Distribution Analysis
$distribution = [];
for ($i = 0; $i < 1000; $i++) {
    $context = new EvaluationContext(
        tenantId: "tenant-{$i}",
        userId: null
    );
    
    $result = $manager->evaluate('feature.rollout_50', $context);
    $distribution[$result ? 'enabled' : 'disabled']++;
}

echo "Enabled: {$distribution['enabled']}, Disabled: {$distribution['disabled']}\n";
// Should be approximately 500/500 for 50% rollout

// Example 3: Tenant Inheritance Override
// Global: 25% rollout
FeatureFlag::create([
    'tenant_id' => null,
    'key' => 'feature.new_analytics',
    'strategy' => 'percentage_rollout',
    'percentage' => 25,
]);

// Tenant-specific: 100% enabled
FeatureFlag::create([
    'tenant_id' => 'tenant-premium-001',
    'key' => 'feature.new_analytics',
    'strategy' => 'system_wide',
    'enabled' => true,
]);
