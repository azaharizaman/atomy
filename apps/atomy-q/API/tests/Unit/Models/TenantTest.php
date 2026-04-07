<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

final class TenantTest extends TestCase
{
    public function test_it_exposes_defaults_and_date_helpers(): void
    {
        $tenant = new Tenant();

        $this->assertSame('', $tenant->getCode());
        $this->assertSame('', $tenant->getName());
        $this->assertSame('', $tenant->getEmail());
        $this->assertSame('pending', $tenant->getStatus());
        $this->assertNull($tenant->getDomain());
        $this->assertNull($tenant->getSubdomain());
        $this->assertNull($tenant->getDatabaseName());
        $this->assertSame('UTC', $tenant->getTimezone());
        $this->assertSame('en', $tenant->getLocale());
        $this->assertSame('USD', $tenant->getCurrency());
        $this->assertSame('Y-m-d', $tenant->getDateFormat());
        $this->assertSame('H:i', $tenant->getTimeFormat());
        $this->assertNull($tenant->getParentId());
        $this->assertSame([], $tenant->getMetadata());
        $this->assertSame('fallback', $tenant->getMetadataValue('missing', 'fallback'));
        $this->assertFalse($tenant->isActive());
        $this->assertFalse($tenant->isSuspended());
        $this->assertFalse($tenant->isTrial());
        $this->assertFalse($tenant->isArchived());
        $this->assertNull($tenant->getTrialEndsAt());
        $this->assertFalse($tenant->isTrialExpired());
        $this->assertNull($tenant->getStorageQuota());
        $this->assertSame(0, $tenant->getStorageUsed());
        $this->assertNull($tenant->getMaxUsers());
        $this->assertNull($tenant->getRateLimit());
        $this->assertFalse($tenant->isReadOnly());
        $this->assertNull($tenant->getBillingCycleStartDate());
        $this->assertSame(0, $tenant->getOnboardingProgress());
        $this->assertNotNull($tenant->getCreatedAt());
        $this->assertNull($tenant->getUpdatedAt());
        $this->assertNull($tenant->getDeletedAt());
        $this->assertNull($tenant->getRetentionHoldUntil());
        $this->assertFalse($tenant->isQueuedForDeletion());
    }

    public function test_it_exposes_normalized_values_and_relationships(): void
    {
        $tenant = new Tenant([
            'id' => 'tenant-123',
            'code' => '  acme  ',
            'name' => '  Acme Procurement  ',
            'email' => '  owner@acme.test  ',
            'status' => 'active',
            'domain' => 'acme.test',
            'subdomain' => 'acme',
            'database_name' => 'tenant_acme',
            'timezone' => 'Asia/Kuala_Lumpur',
            'locale' => 'ms-MY',
            'currency' => 'MYR',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'parent_id' => 'tenant-parent',
            'metadata' => ['source' => 'register-company'],
            'trial_ends_at' => now()->addDay(),
            'storage_quota' => 1024,
            'storage_used' => 512,
            'max_users' => 25,
            'rate_limit' => 60,
            'is_readonly' => true,
            'billing_cycle_starts_at' => now()->addMonth(),
            'onboarding_progress' => 80,
            'retention_hold_until' => now()->addDays(3),
            'created_at' => '2026-04-07 10:15:00',
            'updated_at' => '2026-04-07 11:15:00',
            'deleted_at' => '2026-04-07 12:15:00',
        ]);

        $this->assertSame('acme', $tenant->getCode());
        $this->assertSame('Acme Procurement', $tenant->getName());
        $this->assertSame('owner@acme.test', $tenant->getEmail());
        $this->assertSame('active', $tenant->getStatus());
        $this->assertSame('acme.test', $tenant->getDomain());
        $this->assertSame('acme', $tenant->getSubdomain());
        $this->assertSame('tenant_acme', $tenant->getDatabaseName());
        $this->assertSame('Asia/Kuala_Lumpur', $tenant->getTimezone());
        $this->assertSame('ms-MY', $tenant->getLocale());
        $this->assertSame('MYR', $tenant->getCurrency());
        $this->assertSame('d/m/Y', $tenant->getDateFormat());
        $this->assertSame('H:i', $tenant->getTimeFormat());
        $this->assertSame('tenant-parent', $tenant->getParentId());
        $this->assertSame(['source' => 'register-company'], $tenant->getMetadata());
        $this->assertSame('register-company', $tenant->getMetadataValue('source'));
        $this->assertTrue($tenant->isActive());
        $this->assertFalse($tenant->isSuspended());
        $this->assertFalse($tenant->isTrial());
        $this->assertFalse($tenant->isArchived());
        $this->assertFalse($tenant->isTrialExpired());
        $this->assertSame(1024, $tenant->getStorageQuota());
        $this->assertSame(512, $tenant->getStorageUsed());
        $this->assertSame(25, $tenant->getMaxUsers());
        $this->assertSame(60, $tenant->getRateLimit());
        $this->assertTrue($tenant->isReadOnly());
        $this->assertSame(80, $tenant->getOnboardingProgress());
        $this->assertTrue($tenant->isQueuedForDeletion());

        $this->assertInstanceOf(BelongsTo::class, $tenant->parent());
        $this->assertInstanceOf(HasMany::class, $tenant->children());
        $this->assertInstanceOf(HasMany::class, $tenant->users());
    }
}
