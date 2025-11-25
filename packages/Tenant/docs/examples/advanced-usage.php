<?php

/**
 * Advanced Usage Examples for Nexus\Tenant Package
 * 
 * This file demonstrates advanced patterns and integration scenarios.
 */

declare(strict_types=1);

use Nexus\Tenant\Services\{
    TenantLifecycleService,
    TenantContextManager,
    TenantImpersonationService
};
use Nexus\Tenant\Events\{
    TenantCreatedEvent,
    TenantSuspendedEvent,
    ImpersonationStartedEvent
};

// ============================================================================
// EXAMPLE 1: Tenant Impersonation for Support Staff
// ============================================================================

/** @var TenantImpersonationService $impersonationService */
$impersonationService = app(TenantImpersonationService::class);

/** @var TenantContextManager $contextManager */
$contextManager = app(TenantContextManager::class);

// Current admin/support staff tenant
$currentTenantId = 'admin-tenant-123';
$contextManager->setTenant($currentTenantId);

// Impersonate customer tenant for troubleshooting
$customerTenantId = 'customer-tenant-456';
$impersonationService->impersonate(
    storageKey: 'support_session_' . session_id(),
    tenantId: $customerTenantId,
    impersonatorId: auth()->id(),
    reason: 'Customer support troubleshooting - Ticket #12345'
);

echo "Now impersonating tenant: {$customerTenantId}\n";

// Check impersonation status
if ($impersonationService->isImpersonating('support_session_' . session_id())) {
    echo "Impersonation is active\n";
    
    $impersonatedId = $impersonationService->getImpersonatedTenantId('support_session_' . session_id());
    echo "Impersonated tenant: {$impersonatedId}\n";
    
    $originalId = $impersonationService->getOriginalTenantId('support_session_' . session_id());
    echo "Original tenant: {$originalId}\n";
}

// Perform troubleshooting actions as customer...
// ...

// Stop impersonation and restore original context
$impersonationService->stopImpersonation('support_session_' . session_id());
echo "Impersonation stopped, restored to original tenant\n";

// ============================================================================
// EXAMPLE 2: Event-Driven Tenant Provisioning
// ============================================================================

use Illuminate\Support\Facades\Event;

// Listen to tenant created event
Event::listen(TenantCreatedEvent::class, function (TenantCreatedEvent $event) {
    $tenant = $event->tenant;
    
    // Provision tenant resources asynchronously
    dispatch(new \App\Jobs\ProvisionTenantDatabase($tenant));
    dispatch(new \App\Jobs\CreateDefaultAdminUser($tenant));
    dispatch(new \App\Jobs\SeedDefaultSettings($tenant));
    dispatch(new \App\Jobs\SendWelcomeEmail($tenant));
    
    echo "Tenant provisioning jobs dispatched for: {$tenant->getCode()}\n";
});

// Create tenant (triggers event)
/** @var TenantLifecycleService $lifecycleService */
$lifecycleService = app(TenantLifecycleService::class);

$newTenant = $lifecycleService->createTenant(
    code: 'STARTUP',
    name: 'Startup Inc',
    email: 'admin@startup.com',
    domain: 'startup.yourapp.com'
);

// ============================================================================
// EXAMPLE 3: Multi-Strategy Tenant Resolution with Fallback
// ============================================================================

use Nexus\Tenant\Services\TenantResolverService;

/** @var TenantResolverService $resolver */
$resolver = app(TenantResolverService::class);

function resolveTenantFromRequest($request): ?\Nexus\Tenant\Contracts\TenantInterface
{
    global $resolver;
    
    // Strategy 1: Custom domain (highest priority)
    $host = $request->getHost();
    if (!str_ends_with($host, '.yourapp.com')) {
        $tenant = $resolver->resolveByDomain($host);
        if ($tenant) {
            return $tenant;
        }
    }
    
    // Strategy 2: Subdomain
    $parts = explode('.', $host);
    if (count($parts) >= 3) {
        $subdomain = $parts[0];
        $tenant = $resolver->resolveBySubdomain($subdomain);
        if ($tenant) {
            return $tenant;
        }
    }
    
    // Strategy 3: Header (for API requests)
    $tenantCode = $request->header('X-Tenant-Code');
    if ($tenantCode) {
        $tenant = $resolver->resolveByCode($tenantCode);
        if ($tenant) {
            return $tenant;
        }
    }
    
    // Strategy 4: Path segment (e.g., /tenant/ACME/dashboard)
    $path = $request->getPathInfo();
    if (preg_match('#^/tenant/([A-Z0-9]+)/#', $path, $matches)) {
        $tenant = $resolver->resolveByCode($matches[1]);
        if ($tenant) {
            return $tenant;
        }
    }
    
    // Strategy 5: Session (for logged-in users)
    $tenantId = session('tenant_id');
    if ($tenantId) {
        $tenant = app(\Nexus\Tenant\Contracts\TenantQueryInterface::class)
            ->findById($tenantId);
        if ($tenant) {
            return $tenant;
        }
    }
    
    return null;
}

// ============================================================================
// EXAMPLE 4: Tenant-Specific Database Connection Management
// ============================================================================

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class TenantDatabaseManager
{
    public function __construct(
        private readonly TenantContextManager $contextManager
    ) {}
    
    public function switchToTenantDatabase(): void
    {
        $tenant = $this->contextManager->getCurrentTenant();
        
        if (!$tenant) {
            throw new \RuntimeException('No tenant context set');
        }
        
        $metadata = $tenant->getMetadata();
        
        // Each tenant has their own database
        $dbName = $metadata['database_name'] ?? 'tenant_' . $tenant->getCode();
        
        Config::set('database.connections.tenant', [
            'driver' => 'mysql',
            'host' => env('DB_HOST'),
            'database' => $dbName,
            'username' => $metadata['db_username'] ?? env('DB_USERNAME'),
            'password' => $metadata['db_password'] ?? env('DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);
        
        DB::purge('tenant');
        DB::reconnect('tenant');
        DB::setDefaultConnection('tenant');
    }
    
    public function switchToSystemDatabase(): void
    {
        DB::setDefaultConnection('mysql');
    }
}

// Usage in middleware
$dbManager = new TenantDatabaseManager($contextManager);
$dbManager->switchToTenantDatabase();

// All queries now run on tenant database
$invoices = DB::table('invoices')->get();

// Switch back to system database
$dbManager->switchToSystemDatabase();

// ============================================================================
// EXAMPLE 5: Tenant-Aware Eloquent Global Scope
// ============================================================================

use Illuminate\Database\Eloquent\{Builder, Model, Scope};

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $contextManager = app(TenantContextManager::class);
        
        if ($contextManager->hasTenant()) {
            $builder->where(
                $model->getTable() . '.tenant_id',
                $contextManager->getCurrentTenantId()
            );
        }
    }
}

// Apply to model
class Invoice extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
        
        // Automatically set tenant_id on create
        static::creating(function (Invoice $invoice) {
            $contextManager = app(TenantContextManager::class);
            $invoice->tenant_id = $contextManager->requireTenant();
        });
    }
}

// ============================================================================
// EXAMPLE 6: Tenant Suspension with Automatic Cleanup
// ============================================================================

Event::listen(TenantSuspendedEvent::class, function (TenantSuspendedEvent $event) {
    $tenant = $event->tenant;
    $reason = $event->reason;
    
    // Send suspension notification
    dispatch(new \App\Jobs\SendTenantSuspensionEmail($tenant, $reason));
    
    // Disable all active sessions
    dispatch(new \App\Jobs\RevokeAllTenantSessions($tenant));
    
    // Pause scheduled jobs
    dispatch(new \App\Jobs\PauseScheduledJobs($tenant));
    
    // Log to audit system
    \App\Services\AuditLogger::log('tenant.suspended', [
        'tenant_id' => $tenant->getId(),
        'tenant_code' => $tenant->getCode(),
        'reason' => $reason,
    ]);
});

// Suspend tenant with reason
$suspendedTenant = $lifecycleService->suspendTenant(
    tenantId: 'tenant-789',
    reason: 'Payment failed for 3 consecutive months'
);

// ============================================================================
// EXAMPLE 7: Tenant-Aware Queue Jobs
// ============================================================================

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};

class ProcessTenantReport implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        private readonly string $reportId,
        private readonly string $tenantId
    ) {}
    
    public function handle(TenantContextManager $contextManager): void
    {
        // Set tenant context before processing
        $contextManager->setTenant($this->tenantId);
        
        try {
            // Process report with tenant context
            $report = Report::findOrFail($this->reportId);
            $report->process();
            
        } finally {
            // Clear tenant context
            $contextManager->clearTenant();
        }
    }
}

// Dispatch job with tenant context
$contextManager->setTenant('tenant-abc');
dispatch(new ProcessTenantReport('report-123', $contextManager->requireTenant()));

// ============================================================================
// EXAMPLE 8: Hierarchical Tenant Permissions
// ============================================================================

use Nexus\Tenant\Contracts\TenantQueryInterface;

class HierarchicalTenantManager
{
    public function __construct(
        private readonly TenantQueryInterface $query,
        private readonly TenantContextManager $contextManager
    ) {}
    
    public function canAccessChildTenant(string $childTenantId): bool
    {
        $currentTenantId = $this->contextManager->getCurrentTenantId();
        
        if (!$currentTenantId) {
            return false;
        }
        
        $childTenant = $this->query->findById($childTenantId);
        
        if (!$childTenant) {
            return false;
        }
        
        // Check if current tenant is parent
        if ($childTenant->getParentId() === $currentTenantId) {
            return true;
        }
        
        // Check if current tenant is ancestor (recursive)
        return $this->isAncestorOf($currentTenantId, $childTenantId);
    }
    
    private function isAncestorOf(string $ancestorId, string $descendantId): bool
    {
        $tenant = $this->query->findById($descendantId);
        
        while ($tenant && $tenant->getParentId()) {
            if ($tenant->getParentId() === $ancestorId) {
                return true;
            }
            
            $tenant = $this->query->findById($tenant->getParentId());
        }
        
        return false;
    }
    
    public function getAllDescendants(string $tenantId): array
    {
        $descendants = [];
        $children = $this->query->getChildren($tenantId);
        
        foreach ($children as $child) {
            $descendants[] = $child;
            
            // Recursively get grandchildren
            $grandchildren = $this->getAllDescendants($child->getId());
            $descendants = array_merge($descendants, $grandchildren);
        }
        
        return $descendants;
    }
}

// Usage
$hierarchyManager = new HierarchicalTenantManager($query, $contextManager);

// Check access
if ($hierarchyManager->canAccessChildTenant('child-tenant-123')) {
    echo "Access granted to child tenant\n";
}

// Get all descendants
$descendants = $hierarchyManager->getAllDescendants('parent-tenant-456');
echo "Found " . count($descendants) . " descendant tenants\n";

// ============================================================================
// EXAMPLE 9: Tenant Migration Script
// ============================================================================

use Nexus\Tenant\Services\TenantStatusService;

class TenantMigrationScript
{
    public function __construct(
        private readonly TenantStatusService $statusService,
        private readonly TenantLifecycleService $lifecycleService
    ) {}
    
    public function migrateTrialTenantsToActive(): array
    {
        $trialTenants = $this->statusService->getTrialTenants();
        $migrated = [];
        
        foreach ($trialTenants as $tenant) {
            $metadata = $tenant->getMetadata();
            $trialEndDate = $metadata['trial_end_date'] ?? null;
            
            if (!$trialEndDate) {
                continue;
            }
            
            $endDate = new \DateTimeImmutable($trialEndDate);
            $now = new \DateTimeImmutable();
            
            // If trial ended more than 30 days ago, suspend
            if ($endDate < $now->modify('-30 days')) {
                $this->lifecycleService->suspendTenant(
                    $tenant->getId(),
                    'Trial expired over 30 days ago'
                );
                $migrated[] = [
                    'tenant' => $tenant->getCode(),
                    'action' => 'suspended',
                ];
                continue;
            }
            
            // If trial just ended, check if payment method exists
            if ($endDate < $now && isset($metadata['payment_method'])) {
                $this->lifecycleService->activateTenant($tenant->getId());
                $migrated[] = [
                    'tenant' => $tenant->getCode(),
                    'action' => 'activated',
                ];
            }
        }
        
        return $migrated;
    }
}

// Run migration
$migration = new TenantMigrationScript($statusService, $lifecycleService);
$results = $migration->migrateTrialTenantsToActive();

foreach ($results as $result) {
    echo "Tenant {$result['tenant']}: {$result['action']}\n";
}

// ============================================================================
// EXAMPLE 10: Tenant-Specific Feature Flags
// ============================================================================

class TenantFeatureManager
{
    public function __construct(
        private readonly TenantContextManager $contextManager
    ) {}
    
    public function isFeatureEnabled(string $feature): bool
    {
        $tenant = $this->contextManager->getCurrentTenant();
        
        if (!$tenant) {
            return false;
        }
        
        $metadata = $tenant->getMetadata();
        $features = $metadata['enabled_features'] ?? [];
        
        return in_array($feature, $features, true);
    }
    
    public function enableFeature(string $tenantId, string $feature): void
    {
        $lifecycleService = app(TenantLifecycleService::class);
        $query = app(\Nexus\Tenant\Contracts\TenantQueryInterface::class);
        
        $tenant = $query->findById($tenantId);
        $metadata = $tenant->getMetadata();
        $features = $metadata['enabled_features'] ?? [];
        
        if (!in_array($feature, $features, true)) {
            $features[] = $feature;
            
            $lifecycleService->updateTenant($tenantId, [
                'metadata' => array_merge($metadata, [
                    'enabled_features' => $features,
                ]),
            ]);
        }
    }
}

// Usage
$featureManager = new TenantFeatureManager($contextManager);

if ($featureManager->isFeatureEnabled('advanced_reporting')) {
    // Show advanced reporting features
}

// Enable feature for tenant
$featureManager->enableFeature('tenant-xyz', 'api_access');

// ============================================================================
// EXAMPLE 11: Listening to Impersonation Events
// ============================================================================

Event::listen(ImpersonationStartedEvent::class, function (ImpersonationStartedEvent $event) {
    $originalTenant = $event->originalTenant;
    $targetTenant = $event->targetTenant;
    $impersonatorId = $event->impersonatorId;
    
    // Log impersonation for audit trail
    \Log::info('Tenant impersonation started', [
        'impersonator_id' => $impersonatorId,
        'original_tenant' => $originalTenant->getCode(),
        'target_tenant' => $targetTenant->getCode(),
        'timestamp' => now(),
    ]);
    
    // Send notification to tenant admins
    dispatch(new \App\Jobs\NotifyTenantAdmins(
        $targetTenant,
        'Support staff is viewing your account'
    ));
});

// ============================================================================
// EXAMPLE 12: Tenant Health Check
// ============================================================================

class TenantHealthChecker
{
    public function __construct(
        private readonly TenantStatusService $statusService
    ) {}
    
    public function getHealthReport(): array
    {
        $stats = $this->statusService->getStatistics();
        $expiredTrials = $this->statusService->getExpiredTrials();
        
        return [
            'total_tenants' => $stats['total'],
            'active_tenants' => $stats['active'],
            'suspended_tenants' => $stats['suspended'],
            'trial_tenants' => $stats['trial'],
            'expired_trials' => count($expiredTrials),
            'health_score' => $this->calculateHealthScore($stats),
            'alerts' => $this->getAlerts($stats, $expiredTrials),
        ];
    }
    
    private function calculateHealthScore(array $stats): float
    {
        if ($stats['total'] === 0) {
            return 100.0;
        }
        
        $activeRatio = $stats['active'] / $stats['total'];
        $suspendedRatio = $stats['suspended'] / $stats['total'];
        
        return round(($activeRatio * 100) - ($suspendedRatio * 20), 2);
    }
    
    private function getAlerts(array $stats, array $expiredTrials): array
    {
        $alerts = [];
        
        if ($stats['suspended'] > ($stats['total'] * 0.1)) {
            $alerts[] = 'High suspension rate: ' . $stats['suspended'] . ' tenants';
        }
        
        if (count($expiredTrials) > 10) {
            $alerts[] = count($expiredTrials) . ' trial tenants need attention';
        }
        
        return $alerts;
    }
}

$healthChecker = new TenantHealthChecker($statusService);
$report = $healthChecker->getHealthReport();

echo "Tenant Platform Health Report:\n";
echo "Total Tenants: {$report['total_tenants']}\n";
echo "Health Score: {$report['health_score']}%\n";

foreach ($report['alerts'] as $alert) {
    echo "ALERT: {$alert}\n";
}
