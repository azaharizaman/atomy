<?php

/**
 * Basic Usage Examples for Nexus\Tenant Package
 * 
 * This file demonstrates common usage patterns for the Tenant package.
 */

declare(strict_types=1);

use Nexus\Tenant\Services\TenantLifecycleService;
use Nexus\Tenant\Services\TenantContextManager;
use Nexus\Tenant\Services\TenantResolverService;
use Nexus\Tenant\Services\TenantStatusService;

// Assume dependency injection has been set up via your framework's container

// ============================================================================
// EXAMPLE 1: Creating a New Tenant
// ============================================================================

/** @var TenantLifecycleService $lifecycleService */
$lifecycleService = app(TenantLifecycleService::class);

$tenant = $lifecycleService->createTenant(
    code: 'ACME',
    name: 'Acme Corporation',
    email: 'admin@acme.com',
    domain: 'acme.yourapp.com',
    additionalData: [
        'subdomain' => 'acme',
        'metadata' => [
            'industry' => 'Manufacturing',
            'size' => 'Enterprise',
        ],
    ]
);

echo "Created tenant: {$tenant->getName()} (ID: {$tenant->getId()})\n";

// ============================================================================
// EXAMPLE 2: Activating a Tenant
// ============================================================================

$activatedTenant = $lifecycleService->activateTenant($tenant->getId());

echo "Activated tenant: {$activatedTenant->getCode()}\n";
echo "Status: {$activatedTenant->getStatus()}\n";

// ============================================================================
// EXAMPLE 3: Setting Tenant Context
// ============================================================================

/** @var TenantContextManager $contextManager */
$contextManager = app(TenantContextManager::class);

// Set the current tenant
$contextManager->setTenant($tenant->getId());

// Get current tenant ID
$currentTenantId = $contextManager->getCurrentTenantId();
echo "Current tenant ID: {$currentTenantId}\n";

// Get current tenant entity
$currentTenant = $contextManager->getCurrentTenant();
echo "Current tenant name: {$currentTenant->getName()}\n";

// Check if tenant context is set
if ($contextManager->hasTenant()) {
    echo "Tenant context is active\n";
}

// ============================================================================
// EXAMPLE 4: Resolving Tenant by Domain/Subdomain
// ============================================================================

/** @var TenantResolverService $resolver */
$resolver = app(TenantResolverService::class);

// Resolve by full domain
$tenantByDomain = $resolver->resolveByDomain('acme.yourapp.com');
if ($tenantByDomain) {
    echo "Resolved tenant by domain: {$tenantByDomain->getName()}\n";
}

// Resolve by subdomain
$tenantBySubdomain = $resolver->resolveBySubdomain('acme');
if ($tenantBySubdomain) {
    echo "Resolved tenant by subdomain: {$tenantBySubdomain->getName()}\n";
}

// Resolve by code
$tenantByCode = $resolver->resolveByCode('ACME');
if ($tenantByCode) {
    echo "Resolved tenant by code: {$tenantByCode->getName()}\n";
}

// ============================================================================
// EXAMPLE 5: Updating Tenant Information
// ============================================================================

$updatedTenant = $lifecycleService->updateTenant(
    tenantId: $tenant->getId(),
    data: [
        'name' => 'Acme Corporation Ltd',
        'email' => 'info@acme.com',
        'metadata' => [
            'industry' => 'Manufacturing',
            'size' => 'Enterprise',
            'country' => 'Malaysia',
        ],
    ]
);

echo "Updated tenant name: {$updatedTenant->getName()}\n";

// ============================================================================
// EXAMPLE 6: Suspending a Tenant
// ============================================================================

$suspendedTenant = $lifecycleService->suspendTenant(
    tenantId: $tenant->getId(),
    reason: 'Payment overdue'
);

echo "Suspended tenant: {$suspendedTenant->getCode()}\n";
echo "Status: {$suspendedTenant->getStatus()}\n";

// ============================================================================
// EXAMPLE 7: Reactivating a Tenant
// ============================================================================

$reactivatedTenant = $lifecycleService->reactivateTenant($tenant->getId());

echo "Reactivated tenant: {$reactivatedTenant->getCode()}\n";
echo "Status: {$reactivatedTenant->getStatus()}\n";

// ============================================================================
// EXAMPLE 8: Using Status Service for Filtering
// ============================================================================

/** @var TenantStatusService $statusService */
$statusService = app(TenantStatusService::class);

// Get all active tenants
$activeTenants = $statusService->getActiveTenants();
echo "Active tenants count: " . count($activeTenants) . "\n";

// Get all suspended tenants
$suspendedTenants = $statusService->getSuspendedTenants();
echo "Suspended tenants count: " . count($suspendedTenants) . "\n";

// Get tenant statistics
$stats = $statusService->getStatistics();
echo "Tenant Statistics:\n";
echo "  Total: {$stats['total']}\n";
echo "  Active: {$stats['active']}\n";
echo "  Suspended: {$stats['suspended']}\n";
echo "  Trial: {$stats['trial']}\n";

// ============================================================================
// EXAMPLE 9: Archiving (Soft Delete) a Tenant
// ============================================================================

$archived = $lifecycleService->archiveTenant(
    tenantId: $tenant->getId(),
    reason: 'Account closed by customer request'
);

if ($archived) {
    echo "Tenant archived successfully\n";
}

// ============================================================================
// EXAMPLE 10: Checking Tenant Status
// ============================================================================

if ($currentTenant->isActive()) {
    echo "Tenant is active\n";
}

if ($currentTenant->isSuspended()) {
    echo "Tenant is suspended\n";
}

if ($currentTenant->isTrial()) {
    echo "Tenant is in trial period\n";
}

// ============================================================================
// EXAMPLE 11: Working with Hierarchical Tenants (Parent/Child)
// ============================================================================

// Create parent tenant
$parentTenant = $lifecycleService->createTenant(
    code: 'PARENT',
    name: 'Parent Organization',
    email: 'parent@example.com',
    domain: null
);

// Create child tenant
$childTenant = $lifecycleService->createTenant(
    code: 'CHILD01',
    name: 'Child Organization 1',
    email: 'child01@example.com',
    domain: null,
    additionalData: [
        'parent_id' => $parentTenant->getId(),
    ]
);

if ($childTenant->getParentId()) {
    echo "Child tenant created under parent: {$childTenant->getParentId()}\n";
}

// ============================================================================
// EXAMPLE 12: Clearing Tenant Context
// ============================================================================

$contextManager->clearTenant();

if (!$contextManager->hasTenant()) {
    echo "Tenant context cleared\n";
}

// ============================================================================
// EXAMPLE 13: Requiring Tenant Context (with Exception)
// ============================================================================

try {
    $requiredTenantId = $contextManager->requireTenant();
    echo "Required tenant ID: {$requiredTenantId}\n";
} catch (\Nexus\Tenant\Exceptions\TenantContextNotSetException $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Tenant context must be set before calling this method\n";
}

// ============================================================================
// EXAMPLE 14: Refreshing Tenant Cache
// ============================================================================

$contextManager->setTenant($tenant->getId());

// Refresh cache for specific tenant
$contextManager->refreshTenantCache($tenant->getId());
echo "Tenant cache refreshed\n";

// Clear all tenant caches
$contextManager->clearAllTenantCaches();
echo "All tenant caches cleared\n";

// ============================================================================
// EXAMPLE 15: Handling Duplicate Code/Domain Exceptions
// ============================================================================

use Nexus\Tenant\Exceptions\DuplicateTenantCodeException;
use Nexus\Tenant\Exceptions\DuplicateTenantDomainException;

try {
    $duplicateTenant = $lifecycleService->createTenant(
        code: 'ACME', // Already exists
        name: 'Duplicate Tenant',
        email: 'duplicate@example.com',
        domain: null
    );
} catch (DuplicateTenantCodeException $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Tenant code 'ACME' already exists\n";
}

try {
    $duplicateDomain = $lifecycleService->createTenant(
        code: 'NEW',
        name: 'New Tenant',
        email: 'new@example.com',
        domain: 'acme.yourapp.com' // Already exists
    );
} catch (DuplicateTenantDomainException $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Domain 'acme.yourapp.com' is already assigned\n";
}

// ============================================================================
// EXAMPLE 16: Working with Tenant Metadata
// ============================================================================

$metadata = $currentTenant->getMetadata();

if (isset($metadata['industry'])) {
    echo "Tenant industry: {$metadata['industry']}\n";
}

if (isset($metadata['size'])) {
    echo "Tenant size: {$metadata['size']}\n";
}

// Update metadata via lifecycle service
$updatedWithMetadata = $lifecycleService->updateTenant(
    tenantId: $currentTenant->getId(),
    data: [
        'metadata' => array_merge($metadata, [
            'subscription_tier' => 'premium',
            'max_users' => 100,
        ]),
    ]
);

echo "Metadata updated successfully\n";
