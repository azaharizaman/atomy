<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Tests\Unit\Core\Repository;

use Nexus\FeatureFlags\Core\Repository\InMemoryFlagRepository;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Nexus\FeatureFlags\ValueObjects\FlagDefinition;
use PHPUnit\Framework\TestCase;

final class InMemoryFlagRepositoryTest extends TestCase
{
    private InMemoryFlagRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryFlagRepository();
    }

    /** @test */
    public function it_saves_and_finds_global_flag(): void
    {
        $flag = new FlagDefinition(
            name: 'test_feature',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );

        $this->repository->save($flag);
        $found = $this->repository->find('test_feature');

        $this->assertNotNull($found);
        $this->assertSame('test_feature', $found->getName());
    }

    /** @test */
    public function it_returns_null_when_flag_not_found(): void
    {
        $found = $this->repository->find('nonexistent');

        $this->assertNull($found);
    }

    /** @test */
    public function it_saves_and_finds_tenant_specific_flag(): void
    {
        $flag = new FlagDefinition(
            name: 'premium_feature',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null,
            metadata: ['tenant_id' => 'tenant-123']
        );

        $this->repository->save($flag);
        $found = $this->repository->find('premium_feature', 'tenant-123');

        $this->assertNotNull($found);
        $this->assertSame('premium_feature', $found->getName());
    }

    /** @test */
    public function it_implements_tenant_inheritance_tenant_overrides_global(): void
    {
        // Save global flag
        $globalFlag = new FlagDefinition(
            name: 'feature',
            enabled: false,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );
        $this->repository->save($globalFlag);

        // Save tenant-specific flag
        $tenantFlag = new FlagDefinition(
            name: 'feature',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null,
            metadata: ['tenant_id' => 'tenant-123']
        );
        $this->repository->save($tenantFlag);

        // Should return tenant-specific flag
        $found = $this->repository->find('feature', 'tenant-123');
        $this->assertTrue($found->isEnabled());

        // Should return global flag for different tenant
        $found2 = $this->repository->find('feature', 'tenant-456');
        $this->assertFalse($found2->isEnabled());

        // Should return global flag when no tenant specified
        $found3 = $this->repository->find('feature');
        $this->assertFalse($found3->isEnabled());
    }

    /** @test */
    public function it_falls_back_to_global_when_tenant_specific_not_found(): void
    {
        $globalFlag = new FlagDefinition(
            name: 'global_feature',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );
        $this->repository->save($globalFlag);

        // Request with tenant ID should fallback to global
        $found = $this->repository->find('global_feature', 'tenant-123');

        $this->assertNotNull($found);
        $this->assertTrue($found->isEnabled());
    }

    /** @test */
    public function it_deletes_global_flag(): void
    {
        $flag = new FlagDefinition(
            name: 'to_delete',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );
        $this->repository->save($flag);

        $this->assertNotNull($this->repository->find('to_delete'));

        $this->repository->delete('to_delete');

        $this->assertNull($this->repository->find('to_delete'));
    }

    /** @test */
    public function it_deletes_tenant_specific_flag(): void
    {
        $flag = new FlagDefinition(
            name: 'tenant_feature',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null,
            metadata: ['tenant_id' => 'tenant-123']
        );
        $this->repository->save($flag);

        $this->repository->delete('tenant_feature', 'tenant-123');

        $this->assertNull($this->repository->find('tenant_feature', 'tenant-123'));
    }

    /** @test */
    public function it_deletes_only_specified_tenant_flag(): void
    {
        // Save global flag
        $globalFlag = new FlagDefinition(
            name: 'feature',
            enabled: false,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );
        $this->repository->save($globalFlag);

        // Save tenant-specific flag
        $tenantFlag = new FlagDefinition(
            name: 'feature',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null,
            metadata: ['tenant_id' => 'tenant-123']
        );
        $this->repository->save($tenantFlag);

        // Delete tenant-specific flag
        $this->repository->delete('feature', 'tenant-123');

        // Global flag should still exist
        $found = $this->repository->find('feature');
        $this->assertNotNull($found);
        $this->assertFalse($found->isEnabled());

        // Tenant should now fallback to global
        $foundTenant = $this->repository->find('feature', 'tenant-123');
        $this->assertNotNull($foundTenant);
        $this->assertFalse($foundTenant->isEnabled());
    }

    /** @test */
    public function it_finds_many_flags(): void
    {
        $flag1 = new FlagDefinition('feature_a', true, FlagStrategy::SYSTEM_WIDE, null);
        $flag2 = new FlagDefinition('feature_b', false, FlagStrategy::SYSTEM_WIDE, null);
        $flag3 = new FlagDefinition('feature_c', true, FlagStrategy::SYSTEM_WIDE, null);

        $this->repository->save($flag1);
        $this->repository->save($flag2);
        $this->repository->save($flag3);

        $found = $this->repository->findMany(['feature_a', 'feature_b', 'feature_c']);

        $this->assertCount(3, $found);
        $this->assertArrayHasKey('feature_a', $found);
        $this->assertArrayHasKey('feature_b', $found);
        $this->assertArrayHasKey('feature_c', $found);
    }

    /** @test */
    public function it_finds_many_with_mixed_existence(): void
    {
        $flag1 = new FlagDefinition('exists', true, FlagStrategy::SYSTEM_WIDE, null);
        $this->repository->save($flag1);

        $found = $this->repository->findMany(['exists', 'not_exists']);

        $this->assertCount(1, $found);
        $this->assertArrayHasKey('exists', $found);
        $this->assertArrayNotHasKey('not_exists', $found);
    }

    /** @test */
    public function it_finds_many_with_tenant_inheritance(): void
    {
        // Global flags
        $globalA = new FlagDefinition('feature_a', false, FlagStrategy::SYSTEM_WIDE, null);
        $globalB = new FlagDefinition('feature_b', false, FlagStrategy::SYSTEM_WIDE, null);
        $this->repository->save($globalA);
        $this->repository->save($globalB);

        // Tenant-specific flag overriding feature_a
        $tenantA = new FlagDefinition(
            'feature_a',
            true,
            FlagStrategy::SYSTEM_WIDE,
            null,
            metadata: ['tenant_id' => 'tenant-123']
        );
        $this->repository->save($tenantA);

        $found = $this->repository->findMany(['feature_a', 'feature_b'], 'tenant-123');

        $this->assertCount(2, $found);
        $this->assertTrue($found['feature_a']->isEnabled()); // Tenant-specific
        $this->assertFalse($found['feature_b']->isEnabled()); // Global fallback
    }

    /** @test */
    public function it_returns_all_global_flags(): void
    {
        $flag1 = new FlagDefinition('global_a', true, FlagStrategy::SYSTEM_WIDE, null);
        $flag2 = new FlagDefinition('global_b', false, FlagStrategy::SYSTEM_WIDE, null);
        $tenantFlag = new FlagDefinition(
            'tenant_only',
            true,
            FlagStrategy::SYSTEM_WIDE,
            null,
            metadata: ['tenant_id' => 'tenant-123']
        );

        $this->repository->save($flag1);
        $this->repository->save($flag2);
        $this->repository->save($tenantFlag);

        $allGlobal = $this->repository->all();

        $this->assertCount(2, $allGlobal);
        // Extract names to verify
        $names = array_map(fn($f) => $f->getName(), $allGlobal);
        $this->assertContains('global_a', $names);
        $this->assertContains('global_b', $names);
        $this->assertNotContains('tenant_only', $names);
    }

    /** @test */
    public function it_returns_all_flags_for_tenant_with_inheritance(): void
    {
        // Global flags
        $globalA = new FlagDefinition('global_a', true, FlagStrategy::SYSTEM_WIDE, null);
        $globalB = new FlagDefinition('global_b', false, FlagStrategy::SYSTEM_WIDE, null);
        $this->repository->save($globalA);
        $this->repository->save($globalB);

        // Tenant-specific flags
        $tenantA = new FlagDefinition(
            'global_a',
            false,
            FlagStrategy::SYSTEM_WIDE,
            null,
            metadata: ['tenant_id' => 'tenant-123']
        ); // Overrides global_a
        $tenantOnly = new FlagDefinition(
            'tenant_only',
            true,
            FlagStrategy::SYSTEM_WIDE,
            null,
            metadata: ['tenant_id' => 'tenant-123']
        );
        $this->repository->save($tenantA);
        $this->repository->save($tenantOnly);

        $allTenant = $this->repository->all('tenant-123');

        $this->assertCount(3, $allTenant);
        // Should include: tenant-specific global_a, global_b (inherited), tenant_only
        $names = array_map(fn($f) => $f->getName(), $allTenant);
        $this->assertContains('global_a', $names);
        $this->assertContains('global_b', $names);
        $this->assertContains('tenant_only', $names);

        // Verify global_a uses tenant override
        $globalAFlag = array_filter($allTenant, fn($f) => $f->getName() === 'global_a');
        $globalAFlag = array_values($globalAFlag)[0];
        $this->assertFalse($globalAFlag->isEnabled());
    }

    /** @test */
    public function it_handles_empty_repository(): void
    {
        $this->assertNull($this->repository->find('anything'));
        $this->assertSame([], $this->repository->findMany(['a', 'b']));
        $this->assertSame([], $this->repository->all());
    }

    /** @test */
    public function it_updates_flag_on_subsequent_save(): void
    {
        $flag1 = new FlagDefinition('feature', true, FlagStrategy::SYSTEM_WIDE, null);
        $this->repository->save($flag1);

        $found1 = $this->repository->find('feature');
        $this->assertTrue($found1->isEnabled());

        // Save updated version
        $flag2 = new FlagDefinition('feature', false, FlagStrategy::SYSTEM_WIDE, null);
        $this->repository->save($flag2);

        $found2 = $this->repository->find('feature');
        $this->assertFalse($found2->isEnabled());
    }
}
