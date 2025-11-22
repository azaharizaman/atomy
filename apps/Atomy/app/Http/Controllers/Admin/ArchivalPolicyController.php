<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Setting\Services\SettingsManager;

final readonly class ArchivalPolicyController extends Controller
{
    public function __construct(
        private SettingsManager $settings,
        private AuditLogManagerInterface $auditLogger
    ) {}
    
    /**
     * Get current archival policy configuration.
     */
    public function show(): JsonResponse
    {
        $enabled = $this->settings->getBool('settings.archival.enabled', false);
        $retentionDays = $this->settings->getInt('settings.archival.retention_days');
        
        return response()->json([
            'data' => [
                'enabled' => $enabled,
                'retention_days' => $retentionDays,
            ],
        ]);
    }
    
    /**
     * Update archival policy configuration.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'retention_days' => [
                'nullable',
                'integer',
                'min:90', // Minimum 90 days enforced
            ],
        ]);
        
        $oldEnabled = $this->settings->getBool('settings.archival.enabled', false);
        $oldRetentionDays = $this->settings->getInt('settings.archival.retention_days');
        
        $this->settings->set('settings.archival.enabled', $validated['enabled']);
        $this->settings->set('settings.archival.retention_days', $validated['retention_days']);
        
        $this->auditLogger->log(
            entityId: 'archival_policy',
            action: 'policy_updated',
            description: "Archival policy updated by " . auth()->user()->name,
            metadata: [
                'old_enabled' => $oldEnabled,
                'new_enabled' => $validated['enabled'],
                'old_retention_days' => $oldRetentionDays,
                'new_retention_days' => $validated['retention_days'],
                'user_id' => auth()->id(),
            ]
        );
        
        return response()->json([
            'message' => 'Archival policy updated successfully',
            'data' => [
                'enabled' => $validated['enabled'],
                'retention_days' => $validated['retention_days'],
            ],
        ]);
    }
}
