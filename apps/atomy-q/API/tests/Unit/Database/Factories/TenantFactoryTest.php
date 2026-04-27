<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\Tenant;
use Tests\TestCase;

final class TenantFactoryTest extends TestCase
{
    public function test_tenant_factory_creates_valid_instance(): void
    {
        $tenant = Tenant::factory()->create();

        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertNotEmpty($tenant->code);
        $this->assertNotEmpty($tenant->name);
    }

    public function test_tenant_active_state(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $this->assertSame('active', $tenant->status);
    }

    public function test_tenant_suspended_state(): void
    {
        $tenant = Tenant::factory()->suspended()->create();

        $this->assertSame('suspended', $tenant->status);
    }
}