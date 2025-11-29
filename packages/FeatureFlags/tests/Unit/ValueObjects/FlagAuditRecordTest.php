<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\FeatureFlags\Enums\AuditAction;
use Nexus\FeatureFlags\ValueObjects\FlagAuditRecord;
use PHPUnit\Framework\TestCase;

final class FlagAuditRecordTest extends TestCase
{
    // ========================================
    // Construction Tests
    // ========================================

    public function test_creates_valid_audit_record(): void
    {
        $occurredAt = new DateTimeImmutable('2024-11-15 10:30:00');

        $record = new FlagAuditRecord(
            id: 'audit-001',
            flagName: 'new_feature',
            action: AuditAction::CREATED,
            userId: 'user-123',
            tenantId: 'tenant-456',
            before: null,
            after: ['enabled' => true, 'strategy' => 'system_wide'],
            reason: 'Initial flag creation',
            metadata: ['ip' => '192.168.1.1'],
            occurredAt: $occurredAt,
            sequence: 1
        );

        $this->assertSame('audit-001', $record->getId());
        $this->assertSame('new_feature', $record->getFlagName());
        $this->assertSame(AuditAction::CREATED, $record->getAction());
        $this->assertSame('user-123', $record->getUserId());
        $this->assertSame('tenant-456', $record->getTenantId());
        $this->assertNull($record->getBefore());
        $this->assertSame(['enabled' => true, 'strategy' => 'system_wide'], $record->getAfter());
        $this->assertSame('Initial flag creation', $record->getReason());
        $this->assertSame(['ip' => '192.168.1.1'], $record->getMetadata());
        $this->assertSame($occurredAt, $record->getOccurredAt());
        $this->assertSame(1, $record->getSequence());
    }

    public function test_creates_record_with_nullable_fields(): void
    {
        $occurredAt = new DateTimeImmutable();

        $record = new FlagAuditRecord(
            id: 'audit-002',
            flagName: 'test_flag',
            action: AuditAction::UPDATED,
            userId: null,
            tenantId: null,
            before: ['enabled' => true],
            after: ['enabled' => false],
            reason: null,
            metadata: [],
            occurredAt: $occurredAt,
            sequence: null
        );

        $this->assertNull($record->getUserId());
        $this->assertNull($record->getTenantId());
        $this->assertNull($record->getReason());
        $this->assertNull($record->getSequence());
        $this->assertSame([], $record->getMetadata());
    }

    // ========================================
    // isCritical() Tests
    // ========================================

    /** @dataProvider criticalActionProvider */
    public function test_isCritical_returns_true_for_critical_actions(AuditAction $action): void
    {
        $record = new FlagAuditRecord(
            id: 'audit-001',
            flagName: 'test_flag',
            action: $action,
            userId: null,
            tenantId: null,
            before: null,
            after: null,
            reason: null,
            metadata: [],
            occurredAt: new DateTimeImmutable()
        );

        $this->assertTrue($record->isCritical());
    }

    public static function criticalActionProvider(): array
    {
        return [
            'FORCE_DISABLED' => [AuditAction::FORCE_DISABLED],
            'FORCE_ENABLED' => [AuditAction::FORCE_ENABLED],
            'DELETED' => [AuditAction::DELETED],
            'OVERRIDE_CHANGED' => [AuditAction::OVERRIDE_CHANGED],
        ];
    }

    /** @dataProvider nonCriticalActionProvider */
    public function test_isCritical_returns_false_for_non_critical_actions(AuditAction $action): void
    {
        $record = new FlagAuditRecord(
            id: 'audit-001',
            flagName: 'test_flag',
            action: $action,
            userId: null,
            tenantId: null,
            before: null,
            after: null,
            reason: null,
            metadata: [],
            occurredAt: new DateTimeImmutable()
        );

        $this->assertFalse($record->isCritical());
    }

    public static function nonCriticalActionProvider(): array
    {
        return [
            'CREATED' => [AuditAction::CREATED],
            'UPDATED' => [AuditAction::UPDATED],
            'ENABLED_CHANGED' => [AuditAction::ENABLED_CHANGED],
            'STRATEGY_CHANGED' => [AuditAction::STRATEGY_CHANGED],
            'ROLLOUT_CHANGED' => [AuditAction::ROLLOUT_CHANGED],
            'TARGET_LIST_CHANGED' => [AuditAction::TARGET_LIST_CHANGED],
            'OVERRIDE_CLEARED' => [AuditAction::OVERRIDE_CLEARED],
        ];
    }

    // ========================================
    // Defensive Copying (Immutability) Tests
    // ========================================

    public function test_getBefore_returns_defensive_copy(): void
    {
        $before = ['enabled' => true, 'value' => 50];
        $record = new FlagAuditRecord(
            id: 'audit-001',
            flagName: 'test_flag',
            action: AuditAction::UPDATED,
            userId: null,
            tenantId: null,
            before: $before,
            after: null,
            reason: null,
            metadata: [],
            occurredAt: new DateTimeImmutable()
        );

        $returned = $record->getBefore();
        $this->assertSame($before, $returned);

        // Modify the returned array
        $returned['enabled'] = false;
        $returned['new_key'] = 'should not leak';

        // Original should be unchanged
        $this->assertSame($before, $record->getBefore());
    }

    public function test_getAfter_returns_defensive_copy(): void
    {
        $after = ['enabled' => false, 'value' => 75];
        $record = new FlagAuditRecord(
            id: 'audit-001',
            flagName: 'test_flag',
            action: AuditAction::UPDATED,
            userId: null,
            tenantId: null,
            before: null,
            after: $after,
            reason: null,
            metadata: [],
            occurredAt: new DateTimeImmutable()
        );

        $returned = $record->getAfter();
        $this->assertSame($after, $returned);

        // Modify the returned array
        $returned['enabled'] = true;
        $returned['new_key'] = 'should not leak';

        // Original should be unchanged
        $this->assertSame($after, $record->getAfter());
    }

    public function test_getMetadata_returns_defensive_copy(): void
    {
        $metadata = ['ip' => '192.168.1.1', 'user_agent' => 'Chrome'];
        $record = new FlagAuditRecord(
            id: 'audit-001',
            flagName: 'test_flag',
            action: AuditAction::UPDATED,
            userId: null,
            tenantId: null,
            before: null,
            after: null,
            reason: null,
            metadata: $metadata,
            occurredAt: new DateTimeImmutable()
        );

        $returned = $record->getMetadata();
        $this->assertSame($metadata, $returned);

        // Modify the returned array
        $returned['ip'] = '10.0.0.1';
        $returned['new_key'] = 'should not leak';

        // Original should be unchanged
        $this->assertSame($metadata, $record->getMetadata());
    }

    public function test_getBefore_returns_null_for_null_before(): void
    {
        $record = new FlagAuditRecord(
            id: 'audit-001',
            flagName: 'test_flag',
            action: AuditAction::CREATED,
            userId: null,
            tenantId: null,
            before: null,
            after: ['enabled' => true],
            reason: null,
            metadata: [],
            occurredAt: new DateTimeImmutable()
        );

        $this->assertNull($record->getBefore());
    }

    public function test_getAfter_returns_null_for_null_after(): void
    {
        $record = new FlagAuditRecord(
            id: 'audit-001',
            flagName: 'test_flag',
            action: AuditAction::DELETED,
            userId: null,
            tenantId: null,
            before: ['enabled' => true],
            after: null,
            reason: null,
            metadata: [],
            occurredAt: new DateTimeImmutable()
        );

        $this->assertNull($record->getAfter());
    }

    // ========================================
    // fromArray() Tests
    // ========================================

    public function test_fromArray_creates_record_with_all_fields(): void
    {
        $data = [
            'id' => 'audit-123',
            'flag_name' => 'payment_v2',
            'action' => 'flag_force_disabled',
            'user_id' => 'admin-001',
            'tenant_id' => 'tenant-abc',
            'before' => ['enabled' => true, 'override' => null],
            'after' => ['enabled' => true, 'override' => 'force_off'],
            'reason' => 'Emergency kill switch',
            'metadata' => ['ticket' => 'INC-12345'],
            'occurred_at' => '2024-11-15 14:30:00',
            'sequence' => 42,
        ];

        $record = FlagAuditRecord::fromArray($data);

        $this->assertSame('audit-123', $record->getId());
        $this->assertSame('payment_v2', $record->getFlagName());
        $this->assertSame(AuditAction::FORCE_DISABLED, $record->getAction());
        $this->assertSame('admin-001', $record->getUserId());
        $this->assertSame('tenant-abc', $record->getTenantId());
        $this->assertSame(['enabled' => true, 'override' => null], $record->getBefore());
        $this->assertSame(['enabled' => true, 'override' => 'force_off'], $record->getAfter());
        $this->assertSame('Emergency kill switch', $record->getReason());
        $this->assertSame(['ticket' => 'INC-12345'], $record->getMetadata());
        $this->assertSame('2024-11-15 14:30:00', $record->getOccurredAt()->format('Y-m-d H:i:s'));
        $this->assertSame(42, $record->getSequence());
    }

    public function test_fromArray_creates_record_with_minimal_fields(): void
    {
        $data = [
            'id' => 'audit-minimal',
            'flag_name' => 'test_flag',
            'action' => 'flag_created',
            'occurred_at' => '2024-11-15 10:00:00',
        ];

        $record = FlagAuditRecord::fromArray($data);

        $this->assertSame('audit-minimal', $record->getId());
        $this->assertSame('test_flag', $record->getFlagName());
        $this->assertSame(AuditAction::CREATED, $record->getAction());
        $this->assertNull($record->getUserId());
        $this->assertNull($record->getTenantId());
        $this->assertNull($record->getBefore());
        $this->assertNull($record->getAfter());
        $this->assertNull($record->getReason());
        $this->assertSame([], $record->getMetadata());
        $this->assertNull($record->getSequence());
    }

    public function test_fromArray_accepts_DateTimeImmutable_for_occurred_at(): void
    {
        $occurredAt = new DateTimeImmutable('2024-11-15 16:45:00');
        $data = [
            'id' => 'audit-datetime',
            'flag_name' => 'test_flag',
            'action' => 'flag_updated',
            'occurred_at' => $occurredAt,
        ];

        $record = FlagAuditRecord::fromArray($data);

        $this->assertSame($occurredAt, $record->getOccurredAt());
    }

    // ========================================
    // toArray() Tests
    // ========================================

    public function test_toArray_returns_complete_representation(): void
    {
        $occurredAt = new DateTimeImmutable('2024-11-15 12:00:00');
        $record = new FlagAuditRecord(
            id: 'audit-toarray',
            flagName: 'dashboard_v2',
            action: AuditAction::STRATEGY_CHANGED,
            userId: 'dev-123',
            tenantId: 'company-xyz',
            before: ['strategy' => 'percentage_rollout', 'value' => 25],
            after: ['strategy' => 'tenant_list', 'value' => ['tenant-a', 'tenant-b']],
            reason: 'Switching from rollout to explicit list',
            metadata: ['source' => 'admin_panel'],
            occurredAt: $occurredAt,
            sequence: 100
        );

        $array = $record->toArray();

        $this->assertSame('audit-toarray', $array['id']);
        $this->assertSame('dashboard_v2', $array['flag_name']);
        $this->assertSame('flag_strategy_changed', $array['action']);
        $this->assertSame('dev-123', $array['user_id']);
        $this->assertSame('company-xyz', $array['tenant_id']);
        $this->assertSame(['strategy' => 'percentage_rollout', 'value' => 25], $array['before']);
        $this->assertSame(['strategy' => 'tenant_list', 'value' => ['tenant-a', 'tenant-b']], $array['after']);
        $this->assertSame('Switching from rollout to explicit list', $array['reason']);
        $this->assertSame(['source' => 'admin_panel'], $array['metadata']);
        $this->assertSame('2024-11-15 12:00:00', $array['occurred_at']);
        $this->assertSame(100, $array['sequence']);
        $this->assertFalse($array['is_critical']); // STRATEGY_CHANGED is not critical
    }

    public function test_toArray_includes_is_critical_for_critical_action(): void
    {
        $record = new FlagAuditRecord(
            id: 'audit-critical',
            flagName: 'payment_gateway',
            action: AuditAction::FORCE_DISABLED,
            userId: 'admin-001',
            tenantId: null,
            before: ['override' => null],
            after: ['override' => 'force_off'],
            reason: 'Kill switch activated',
            metadata: [],
            occurredAt: new DateTimeImmutable()
        );

        $array = $record->toArray();

        $this->assertTrue($array['is_critical']);
    }

    public function test_toArray_returns_defensive_copies_for_arrays(): void
    {
        $record = new FlagAuditRecord(
            id: 'audit-001',
            flagName: 'test_flag',
            action: AuditAction::UPDATED,
            userId: null,
            tenantId: null,
            before: ['enabled' => true],
            after: ['enabled' => false],
            reason: null,
            metadata: ['key' => 'value'],
            occurredAt: new DateTimeImmutable()
        );

        $array = $record->toArray();

        // Modify the returned array
        $array['before']['enabled'] = false;
        $array['after']['enabled'] = true;
        $array['metadata']['new_key'] = 'should not leak';

        // Get fresh array - original data should be unchanged
        $freshArray = $record->toArray();
        $this->assertTrue($freshArray['before']['enabled']);
        $this->assertFalse($freshArray['after']['enabled']);
        $this->assertArrayNotHasKey('new_key', $freshArray['metadata']);
    }

    // ========================================
    // Roundtrip Tests
    // ========================================

    public function test_roundtrip_through_array(): void
    {
        $original = new FlagAuditRecord(
            id: 'audit-roundtrip',
            flagName: 'roundtrip_flag',
            action: AuditAction::ROLLOUT_CHANGED,
            userId: 'user-abc',
            tenantId: 'tenant-xyz',
            before: ['value' => 25],
            after: ['value' => 75],
            reason: 'Increasing rollout percentage',
            metadata: ['deployment' => 'v2.3.0'],
            occurredAt: new DateTimeImmutable('2024-11-15 18:00:00'),
            sequence: 500
        );

        $array = $original->toArray();
        $reconstructed = FlagAuditRecord::fromArray($array);

        $this->assertSame($original->getId(), $reconstructed->getId());
        $this->assertSame($original->getFlagName(), $reconstructed->getFlagName());
        $this->assertSame($original->getAction(), $reconstructed->getAction());
        $this->assertSame($original->getUserId(), $reconstructed->getUserId());
        $this->assertSame($original->getTenantId(), $reconstructed->getTenantId());
        $this->assertSame($original->getBefore(), $reconstructed->getBefore());
        $this->assertSame($original->getAfter(), $reconstructed->getAfter());
        $this->assertSame($original->getReason(), $reconstructed->getReason());
        $this->assertSame($original->getMetadata(), $reconstructed->getMetadata());
        $this->assertSame(
            $original->getOccurredAt()->format('Y-m-d H:i:s'),
            $reconstructed->getOccurredAt()->format('Y-m-d H:i:s')
        );
        $this->assertSame($original->getSequence(), $reconstructed->getSequence());
        $this->assertSame($original->isCritical(), $reconstructed->isCritical());
    }

    // ========================================
    // All Actions Tests
    // ========================================

    /** @dataProvider allActionsProvider */
    public function test_all_audit_actions_are_valid(AuditAction $action): void
    {
        $record = new FlagAuditRecord(
            id: 'audit-' . $action->value,
            flagName: 'test_flag',
            action: $action,
            userId: null,
            tenantId: null,
            before: null,
            after: null,
            reason: null,
            metadata: [],
            occurredAt: new DateTimeImmutable()
        );

        $this->assertSame($action, $record->getAction());
        $this->assertSame($action->value, $record->toArray()['action']);
    }

    public static function allActionsProvider(): array
    {
        return array_map(
            fn(AuditAction $action) => [$action],
            AuditAction::cases()
        );
    }
}
