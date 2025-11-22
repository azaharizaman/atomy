<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Setting\Services\SettingsManager;

final readonly class FeatureFlagController extends Controller
{
    public function __construct(
        private SettingsManager $settings,
        private AuditLogManagerInterface $auditLogger
    ) {}
    
    /**
     * List all feature flags with their current states and hierarchy.
     */
    public function index(): JsonResponse
    {
        $flags = $this->getAllFeatureFlags();
        
        return response()->json([
            'data' => $this->buildHierarchy($flags),
        ]);
    }
    
    /**
     * Enable a specific feature flag.
     */
    public function enable(string $flag): JsonResponse
    {
        $oldValue = $this->settings->get($flag);
        
        $this->settings->set($flag, true);
        
        $this->auditLogger->log(
            entityId: 'feature_flags',
            action: 'flag_enabled',
            description: "Feature flag '{$flag}' enabled by " . auth()->user()->name,
            metadata: [
                'flag' => $flag,
                'old_value' => $oldValue,
                'new_value' => true,
                'user_id' => auth()->id(),
            ]
        );
        
        return response()->json([
            'message' => "Feature '{$flag}' enabled successfully",
            'data' => [
                'flag' => $flag,
                'enabled' => true,
            ],
        ]);
    }
    
    /**
     * Disable a specific feature flag.
     */
    public function disable(string $flag): JsonResponse
    {
        $oldValue = $this->settings->get($flag);
        
        $this->settings->set($flag, false);
        
        $this->auditLogger->log(
            entityId: 'feature_flags',
            action: 'flag_disabled',
            description: "Feature flag '{$flag}' disabled by " . auth()->user()->name,
            metadata: [
                'flag' => $flag,
                'old_value' => $oldValue,
                'new_value' => false,
                'user_id' => auth()->id(),
            ]
        );
        
        return response()->json([
            'message' => "Feature '{$flag}' disabled successfully",
            'data' => [
                'flag' => $flag,
                'enabled' => false,
            ],
        ]);
    }
    
    /**
     * Check the effective state of a feature flag after inheritance.
     */
    public function check(string $flag): JsonResponse
    {
        $storedValue = $this->settings->get($flag);
        $effectiveValue = $this->isEnabled($flag);
        
        return response()->json([
            'data' => [
                'flag' => $flag,
                'stored_value' => $storedValue,
                'effective_value' => $effectiveValue,
                'inherited' => $storedValue === null,
            ],
        ]);
    }
    
    /**
     * Get audit history for a specific feature flag.
     */
    public function audit(string $flag): JsonResponse
    {
        // TODO: Implement audit log retrieval from AuditLogger
        // This requires AuditLogger to support querying by metadata
        
        return response()->json([
            'data' => [
                'flag' => $flag,
                'message' => 'Audit history retrieval not yet implemented',
            ],
        ]);
    }
    
    /**
     * Get summary of orphaned data from disabled features.
     */
    public function getOrphans(): JsonResponse
    {
        // TODO: Implement orphaned data detection
        // Requires analysis of database tables associated with disabled features
        
        return response()->json([
            'data' => [],
            'message' => 'Orphaned data detection not yet implemented',
        ]);
    }
    
    /**
     * Manually archive orphaned data for a specific feature.
     */
    public function archiveOrphans(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'feature' => 'required|string',
            'reason' => 'nullable|string|max:255',
        ]);
        
        // TODO: Implement archival logic
        // Requires moving data from primary tables to {table}_archived
        
        $this->auditLogger->log(
            entityId: 'archival_policy',
            action: 'manual_archive',
            description: "Admin manually archived data for feature '{$validated['feature']}'",
            metadata: [
                'feature' => $validated['feature'],
                'reason' => $validated['reason'] ?? 'Manual archival',
                'user_id' => auth()->id(),
            ]
        );
        
        return response()->json([
            'message' => 'Archival initiated (implementation pending)',
            'data' => [
                'feature' => $validated['feature'],
            ],
        ]);
    }
    
    /**
     * Get all feature flags from settings.
     */
    private function getAllFeatureFlags(): array
    {
        // TODO: Implement efficient retrieval of all feature.* settings
        // This requires SettingsManager to support prefix-based retrieval
        
        return [];
    }
    
    /**
     * Build hierarchical structure of feature flags.
     */
    private function buildHierarchy(array $flags): array
    {
        $hierarchy = [];
        
        foreach ($flags as $flag => $value) {
            $parts = explode('.', $flag);
            $current = &$hierarchy;
            
            foreach ($parts as $part) {
                if (!isset($current[$part])) {
                    $current[$part] = [];
                }
                $current = &$current[$part];
            }
            
            $current['_value'] = $value;
        }
        
        return $hierarchy;
    }
    
    /**
     * Check if a feature flag is enabled (with inheritance).
     */
    private function isEnabled(string $flag): bool
    {
        $endpointFlag = $this->settings->get($flag);
        
        if ($endpointFlag === false) {
            return false;
        }
        
        if ($endpointFlag === true) {
            return $this->checkParents($flag);
        }
        
        // Null = inherit, check parents
        return $this->checkParents($flag);
    }
    
    /**
     * Check parent feature flags for inheritance.
     */
    private function checkParents(string $flag): bool
    {
        $parts = explode('.', $flag);
        
        // Check resource wildcard (features.finance.journal_entry.*)
        if (count($parts) === 4) {
            $resourceFlag = "{$parts[0]}.{$parts[1]}.{$parts[2]}.*";
            $resourceValue = $this->settings->get($resourceFlag);
            
            if ($resourceValue === false) {
                return false;
            }
            
            if ($resourceValue === null) {
                return $this->checkPackageWildcard($parts);
            }
        }
        
        return $this->checkPackageWildcard($parts);
    }
    
    /**
     * Check package wildcard flag.
     */
    private function checkPackageWildcard(array $parts): bool
    {
        $packageFlag = "{$parts[0]}.{$parts[1]}.*";
        $packageValue = $this->settings->get($packageFlag);
        
        return $packageValue !== false; // false blocks, true/null allows
    }
}
