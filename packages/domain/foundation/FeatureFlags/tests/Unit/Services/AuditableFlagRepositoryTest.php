<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Tests\Unit\Services;

use Nexus\FeatureFlags\Contracts\FlagAuditChangeInterface;
use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\FeatureFlags\Enums\AuditAction;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Nexus\FeatureFlags\Services\AuditableFlagRepository;
use PHPUnit\Framework\TestCase;

final class AuditableFlagRepositoryTest extends TestCase
{
    private FlagRepositoryInterface $repository;
    private FlagAuditChangeInterface $auditChange;
    private AuditableFlagRepository $auditableRepo;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(FlagRepositoryInterface::class);
        $this->auditChange = $this->createMock(FlagAuditChangeInterface::class);
        $this->auditableRepo = new AuditableFlagRepository(
            $this->repository,
            $this->auditChange,
            'user-123',
            ['source' => 'tests']
        );
    }

    // ========================================
    // Read Operation Tests (Pass-through)
    // ========================================

    public function test_find_delegates_to_repository(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $this->repository->expects($this->once())
            ->method('find')
            ->with('test.flag', 'tenant-abc')
            ->willReturn($flag);

        $result = $this->auditableRepo->find('test.flag', 'tenant-abc');

        $this->assertSame($flag, $result);
    }

    public function test_find_returns_null_when_flag_not_found(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with('nonexistent.flag', null)
            ->willReturn(null);

        $result = $this->auditableRepo->find('nonexistent.flag');

        $this->assertNull($result);
    }

    public function test_findMany_delegates_to_repository(): void
    {
        $flag1 = $this->createStub(FlagDefinitionInterface::class);
        $flag2 = $this->createStub(FlagDefinitionInterface::class);
        $expected = ['flag.one' => $flag1, 'flag.two' => $flag2];

        $this->repository->expects($this->once())
            ->method('findMany')
            ->with(['flag.one', 'flag.two'], 'tenant-xyz')
            ->willReturn($expected);

        $result = $this->auditableRepo->findMany(['flag.one', 'flag.two'], 'tenant-xyz');

        $this->assertSame($expected, $result);
    }

    public function test_all_delegates_to_repository(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $expected = [$flag];

        $this->repository->expects($this->once())
            ->method('all')
            ->with('tenant-123')
            ->willReturn($expected);

        $result = $this->auditableRepo->all('tenant-123');

        $this->assertSame($expected, $result);
    }

    // ========================================
    // Save Operation Tests (CREATE action)
    // ========================================

    public function test_save_records_create_action_for_new_flag(): void
    {
        $flag = $this->createFlagMock('new.flag', true, FlagStrategy::SYSTEM_WIDE);

        $this->repository->expects($this->once())
            ->method('find')
            ->with('new.flag', null)
            ->willReturn(null); // Flag doesn't exist

        $this->repository->expects($this->once())
            ->method('saveForTenant')
            ->with($flag, null);

        $this->auditChange->expects($this->once())
            ->method('recordChange')
            ->with(
                'new.flag',
                AuditAction::CREATED,
                'user-123',
                null, // no before state
                $this->isType('array'),
                $this->callback(fn($meta) => $meta['source'] === 'tests' && $meta['tenant_id'] === null)
            );

        $this->auditableRepo->save($flag);
    }

    // ========================================
    // Save Operation Tests (UPDATE action)
    // ========================================

    public function test_save_records_update_action_when_flag_exists(): void
    {
        $existing = $this->createFlagMock('existing.flag', true, FlagStrategy::SYSTEM_WIDE);
        $updated = $this->createFlagMock('existing.flag', true, FlagStrategy::SYSTEM_WIDE);

        $this->repository->expects($this->once())
            ->method('find')
            ->with('existing.flag', null)
            ->willReturn($existing);

        $this->repository->expects($this->once())
            ->method('saveForTenant')
            ->with($updated, null);

        $this->auditChange->expects($this->once())
            ->method('recordChange')
            ->with(
                'existing.flag',
                AuditAction::UPDATED,
                'user-123',
                $this->isType('array'), // before state
                $this->isType('array'), // after state
                $this->isType('array')
            );

        $this->auditableRepo->save($updated);
    }

    // ========================================
    // Save Operation Tests (FORCE_DISABLED action)
    // ========================================

    public function test_save_records_force_disabled_action(): void
    {
        $existing = $this->createFlagMock('kill.switch', true, FlagStrategy::SYSTEM_WIDE, null);
        $updated = $this->createFlagMock('kill.switch', true, FlagStrategy::SYSTEM_WIDE, FlagOverride::FORCE_OFF);

        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn($existing);

        $this->repository->expects($this->once())
            ->method('saveForTenant');

        $this->auditChange->expects($this->once())
            ->method('recordChange')
            ->with(
                'kill.switch',
                AuditAction::FORCE_DISABLED,
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $this->auditableRepo->save($updated);
    }

    // ========================================
    // Save Operation Tests (FORCE_ENABLED action)
    // ========================================

    public function test_save_records_force_enabled_action(): void
    {
        $existing = $this->createFlagMock('feature', true, FlagStrategy::SYSTEM_WIDE, null);
        $updated = $this->createFlagMock('feature', true, FlagStrategy::SYSTEM_WIDE, FlagOverride::FORCE_ON);

        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn($existing);

        $this->repository->expects($this->once())
            ->method('saveForTenant');

        $this->auditChange->expects($this->once())
            ->method('recordChange')
            ->with(
                'feature',
                AuditAction::FORCE_ENABLED,
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $this->auditableRepo->save($updated);
    }

    // ========================================
    // Save Operation Tests (OVERRIDE_CLEARED action)
    // ========================================

    public function test_save_records_override_cleared_action(): void
    {
        $existing = $this->createFlagMock('feature', true, FlagStrategy::SYSTEM_WIDE, FlagOverride::FORCE_OFF);
        $updated = $this->createFlagMock('feature', true, FlagStrategy::SYSTEM_WIDE, null);

        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn($existing);

        $this->repository->expects($this->once())
            ->method('saveForTenant');

        $this->auditChange->expects($this->once())
            ->method('recordChange')
            ->with(
                'feature',
                AuditAction::OVERRIDE_CLEARED,
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $this->auditableRepo->save($updated);
    }

    // ========================================
    // Save Operation Tests (ENABLED_CHANGED action)
    // ========================================

    public function test_save_records_enabled_changed_action(): void
    {
        $existing = $this->createFlagMock('feature', true, FlagStrategy::SYSTEM_WIDE);
        $updated = $this->createFlagMock('feature', false, FlagStrategy::SYSTEM_WIDE);

        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn($existing);

        $this->repository->expects($this->once())
            ->method('saveForTenant');

        $this->auditChange->expects($this->once())
            ->method('recordChange')
            ->with(
                'feature',
                AuditAction::ENABLED_CHANGED,
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $this->auditableRepo->save($updated);
    }

    // ========================================
    // Save Operation Tests (STRATEGY_CHANGED action)
    // ========================================

    public function test_save_records_strategy_changed_action(): void
    {
        $existing = $this->createFlagMock('feature', true, FlagStrategy::SYSTEM_WIDE);
        $updated = $this->createFlagMock('feature', true, FlagStrategy::PERCENTAGE_ROLLOUT, null, 50);

        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn($existing);

        $this->repository->expects($this->once())
            ->method('saveForTenant');

        $this->auditChange->expects($this->once())
            ->method('recordChange')
            ->with(
                'feature',
                AuditAction::STRATEGY_CHANGED,
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $this->auditableRepo->save($updated);
    }

    // ========================================
    // Save Operation Tests (ROLLOUT_CHANGED action)
    // ========================================

    public function test_save_records_rollout_changed_action(): void
    {
        $existing = $this->createFlagMock('rollout', true, FlagStrategy::PERCENTAGE_ROLLOUT, null, 25);
        $updated = $this->createFlagMock('rollout', true, FlagStrategy::PERCENTAGE_ROLLOUT, null, 75);

        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn($existing);

        $this->repository->expects($this->once())
            ->method('saveForTenant');

        $this->auditChange->expects($this->once())
            ->method('recordChange')
            ->with(
                'rollout',
                AuditAction::ROLLOUT_CHANGED,
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $this->auditableRepo->save($updated);
    }

    // ========================================
    // Save Operation Tests (TARGET_LIST_CHANGED action)
    // ========================================

    public function test_save_records_target_list_changed_action_for_tenant_list(): void
    {
        $existing = $this->createFlagMock('premium', true, FlagStrategy::TENANT_LIST, null, ['tenant-a']);
        $updated = $this->createFlagMock('premium', true, FlagStrategy::TENANT_LIST, null, ['tenant-a', 'tenant-b']);

        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn($existing);

        $this->repository->expects($this->once())
            ->method('saveForTenant');

        $this->auditChange->expects($this->once())
            ->method('recordChange')
            ->with(
                'premium',
                AuditAction::TARGET_LIST_CHANGED,
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $this->auditableRepo->save($updated);
    }

    public function test_save_records_target_list_changed_action_for_user_list(): void
    {
        $existing = $this->createFlagMock('beta', true, FlagStrategy::USER_LIST, null, ['user-a']);
        $updated = $this->createFlagMock('beta', true, FlagStrategy::USER_LIST, null, ['user-a', 'user-b']);

        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn($existing);

        $this->repository->expects($this->once())
            ->method('saveForTenant');

        $this->auditChange->expects($this->once())
            ->method('recordChange')
            ->with(
                'beta',
                AuditAction::TARGET_LIST_CHANGED,
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $this->auditableRepo->save($updated);
    }

    // ========================================
    // Delete Operation Tests
    // ========================================

    public function test_delete_records_deleted_action(): void
    {
        $existing = $this->createFlagMock('old.flag', true, FlagStrategy::SYSTEM_WIDE);

        $this->repository->expects($this->once())
            ->method('find')
            ->with('old.flag', 'tenant-123')
            ->willReturn($existing);

        $this->repository->expects($this->once())
            ->method('delete')
            ->with('old.flag', 'tenant-123');

        $this->auditChange->expects($this->once())
            ->method('recordChange')
            ->with(
                'old.flag',
                AuditAction::DELETED,
                'user-123',
                $this->isType('array'), // before state
                null, // no after state
                $this->callback(fn($meta) => $meta['tenant_id'] === 'tenant-123')
            );

        $this->auditableRepo->delete('old.flag', 'tenant-123');
    }

    public function test_delete_records_with_null_before_when_flag_not_found(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->repository->expects($this->once())
            ->method('delete');

        $this->auditChange->expects($this->once())
            ->method('recordChange')
            ->with(
                'nonexistent.flag',
                AuditAction::DELETED,
                'user-123',
                null, // flag didn't exist
                null,
                $this->anything()
            );

        $this->auditableRepo->delete('nonexistent.flag');
    }

    // ========================================
    // saveForTenant Tests
    // ========================================

    public function test_saveForTenant_uses_tenant_context(): void
    {
        $flag = $this->createFlagMock('tenant.flag', true, FlagStrategy::SYSTEM_WIDE);

        $this->repository->expects($this->once())
            ->method('find')
            ->with('tenant.flag', 'tenant-xyz')
            ->willReturn(null);

        $this->repository->expects($this->once())
            ->method('saveForTenant')
            ->with($flag, 'tenant-xyz');

        $this->auditChange->expects($this->once())
            ->method('recordChange')
            ->with(
                'tenant.flag',
                AuditAction::CREATED,
                'user-123',
                null,
                $this->isType('array'),
                $this->callback(fn($meta) => $meta['tenant_id'] === 'tenant-xyz')
            );

        $this->auditableRepo->saveForTenant($flag, 'tenant-xyz');
    }

    // ========================================
    // Fluent Interface Tests
    // ========================================

    public function test_withUserId_returns_clone_with_new_user_id(): void
    {
        $newRepo = $this->auditableRepo->withUserId('new-user-456');

        $flag = $this->createFlagMock('test.flag', true, FlagStrategy::SYSTEM_WIDE);

        $this->repository->method('find')->willReturn(null);
        $this->repository->expects($this->once())->method('saveForTenant');

        $this->auditChange->expects($this->once())
            ->method('recordChange')
            ->with(
                'test.flag',
                AuditAction::CREATED,
                'new-user-456', // new user ID
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $newRepo->save($flag);
    }

    public function test_withTenantId_returns_clone_with_current_tenant_context(): void
    {
        $tenantRepo = $this->auditableRepo->withTenantId('context-tenant');

        $flag = $this->createFlagMock('test.flag', true, FlagStrategy::SYSTEM_WIDE);

        $this->repository->expects($this->once())
            ->method('find')
            ->with('test.flag', 'context-tenant');

        $this->repository->expects($this->once())
            ->method('saveForTenant')
            ->with($flag, 'context-tenant');

        $this->auditChange->expects($this->once())
            ->method('recordChange')
            ->with(
                'test.flag',
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->callback(fn($meta) => $meta['tenant_id'] === 'context-tenant')
            );

        $tenantRepo->save($flag);
    }

    public function test_withMetadata_returns_clone_with_additional_metadata(): void
    {
        $newRepo = $this->auditableRepo->withMetadata(['extra' => 'data', 'version' => '2.0']);

        $flag = $this->createFlagMock('test.flag', true, FlagStrategy::SYSTEM_WIDE);

        $this->repository->method('find')->willReturn(null);
        $this->repository->expects($this->once())->method('saveForTenant');

        $this->auditChange->expects($this->once())
            ->method('recordChange')
            ->with(
                'test.flag',
                AuditAction::CREATED,
                'user-123',
                null,
                $this->isType('array'),
                $this->callback(fn($meta) => 
                    $meta['source'] === 'tests' && // original metadata preserved
                    $meta['extra'] === 'data' && 
                    $meta['version'] === '2.0'
                )
            );

        $newRepo->save($flag);
    }

    // ========================================
    // Before/After State Capture Tests
    // ========================================

    public function test_save_captures_correct_before_and_after_state(): void
    {
        $existing = $this->createFlagMock('state.flag', true, FlagStrategy::PERCENTAGE_ROLLOUT, null, 25);
        $updated = $this->createFlagMock('state.flag', true, FlagStrategy::PERCENTAGE_ROLLOUT, null, 75);

        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn($existing);

        $this->repository->expects($this->once())
            ->method('saveForTenant');

        $beforeState = null;
        $afterState = null;

        $this->auditChange->expects($this->once())
            ->method('recordChange')
            ->with(
                'state.flag',
                $this->anything(),
                $this->anything(),
                $this->callback(function ($before) use (&$beforeState) {
                    $beforeState = $before;
                    return true;
                }),
                $this->callback(function ($after) use (&$afterState) {
                    $afterState = $after;
                    return true;
                }),
                $this->anything()
            );

        $this->auditableRepo->save($updated);

        // Verify before state
        $this->assertSame('state.flag', $beforeState['name']);
        $this->assertTrue($beforeState['enabled']);
        $this->assertSame('percentage_rollout', $beforeState['strategy']);
        $this->assertSame(25, $beforeState['value']);

        // Verify after state
        $this->assertSame('state.flag', $afterState['name']);
        $this->assertTrue($afterState['enabled']);
        $this->assertSame('percentage_rollout', $afterState['strategy']);
        $this->assertSame(75, $afterState['value']);
    }

    // ========================================
    // Helper Methods
    // ========================================

    private function createFlagMock(
        string $name,
        bool $enabled,
        FlagStrategy $strategy,
        ?FlagOverride $override = null,
        mixed $value = null
    ): FlagDefinitionInterface {
        $flag = $this->createMock(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn($name);
        $flag->method('isEnabled')->willReturn($enabled);
        $flag->method('getStrategy')->willReturn($strategy);
        $flag->method('getOverride')->willReturn($override);
        $flag->method('getValue')->willReturn($value);
        $flag->method('getMetadata')->willReturn([]);
        return $flag;
    }
}
