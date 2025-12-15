<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services\Approval;

use Nexus\Identity\Contracts\RoleQueryInterface;
use Nexus\Identity\Contracts\UserInterface;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\ProcurementOperations\DTOs\ApprovalLimitCheckRequest;
use Nexus\ProcurementOperations\DTOs\ApprovalLimitCheckResult;
use Nexus\ProcurementOperations\DTOs\ApprovalLimitConfig;
use Nexus\ProcurementOperations\Exceptions\ApprovalLimitsException;
use Nexus\ProcurementOperations\Services\Approval\ApprovalLimitsManager;
use Nexus\ProcurementOperations\ValueObjects\ApprovalAuthority;
use Nexus\Setting\Services\SettingsManager;
use Nexus\Tenant\Contracts\TenantContextInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ApprovalLimitsManager.
 */
final class ApprovalLimitsManagerTest extends TestCase
{
    private SettingsManager&MockObject $settingsManager;
    private UserQueryInterface&MockObject $userQuery;
    private RoleQueryInterface&MockObject $roleQuery;
    private TenantContextInterface&MockObject $tenantContext;
    private ApprovalLimitsManager $manager;

    private const TENANT_ID = 'tenant-1';

    protected function setUp(): void
    {
        $this->settingsManager = $this->createMock(SettingsManager::class);
        $this->userQuery = $this->createMock(UserQueryInterface::class);
        $this->roleQuery = $this->createMock(RoleQueryInterface::class);
        $this->tenantContext = $this->createMock(TenantContextInterface::class);

        $this->tenantContext
            ->method('getCurrentTenantId')
            ->willReturn(self::TENANT_ID);

        $this->manager = new ApprovalLimitsManager(
            $this->settingsManager,
            $this->userQuery,
            $this->roleQuery,
            $this->tenantContext,
        );
    }

    /**
     * Test get configuration returns default when no config exists.
     */
    public function test_get_configuration_returns_default_when_none_exists(): void
    {
        $this->settingsManager
            ->method('getTenantSetting')
            ->willReturn(null);

        $config = $this->manager->getConfiguration(self::TENANT_ID);

        $this->assertInstanceOf(ApprovalLimitConfig::class, $config);
        $this->assertIsArray($config->defaultLimits);
        $this->assertArrayHasKey('purchase_order', $config->defaultLimits);
    }

    /**
     * Test get configuration returns saved config.
     */
    public function test_get_configuration_returns_saved_config(): void
    {
        $savedConfig = [
            'default_limits' => [
                'purchase_order' => 25000_00,
                'vendor_invoice' => 15000_00,
            ],
            'role_limits' => [
                'manager' => ['purchase_order' => 50000_00],
            ],
            'department_limits' => [],
            'user_overrides' => [],
            'thresholds' => [],
        ];

        $this->settingsManager
            ->method('getTenantSetting')
            ->with(self::TENANT_ID, 'procurement.approval_limits')
            ->willReturn(json_encode($savedConfig));

        $config = $this->manager->getConfiguration(self::TENANT_ID);

        $this->assertInstanceOf(ApprovalLimitConfig::class, $config);
        $this->assertSame(25000_00, $config->defaultLimits['purchase_order']);
    }

    /**
     * Test save configuration.
     */
    public function test_save_configuration(): void
    {
        $config = ApprovalLimitConfig::createDefault()
            ->withRoleLimit('manager', 'purchase_order', 75000_00);

        $this->settingsManager
            ->expects($this->once())
            ->method('setTenantSetting')
            ->with(
                self::TENANT_ID,
                'procurement.approval_limits',
                $this->callback(function ($value) {
                    $decoded = json_decode($value, true);
                    return $decoded['role_limits']['manager']['purchase_order'] === 75000_00;
                }),
            );

        $this->manager->saveConfiguration(self::TENANT_ID, $config);
    }

    /**
     * Test get user authority resolves from role.
     */
    public function test_get_user_authority_resolves_from_role(): void
    {
        $userId = 'user-123';

        $savedConfig = [
            'default_limits' => ['purchase_order' => 5000_00],
            'role_limits' => ['manager' => ['purchase_order' => 25000_00]],
            'department_limits' => [],
            'user_overrides' => [],
            'thresholds' => [],
        ];

        $this->settingsManager
            ->method('getTenantSetting')
            ->willReturn(json_encode($savedConfig));

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($userId);

        $this->userQuery
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $this->userQuery
            ->method('getUserRoles')
            ->with($userId)
            ->willReturn(['manager', 'viewer']);

        $authority = $this->manager->getUserAuthority($userId);

        $this->assertInstanceOf(ApprovalAuthority::class, $authority);
        $this->assertSame($userId, $authority->userId);
        $this->assertContains('manager', $authority->roles);
    }

    /**
     * Test get user authority with user override takes precedence.
     */
    public function test_get_user_authority_user_override_takes_precedence(): void
    {
        $userId = 'user-123';

        $savedConfig = [
            'default_limits' => ['purchase_order' => 5000_00],
            'role_limits' => ['manager' => ['purchase_order' => 25000_00]],
            'department_limits' => [],
            'user_overrides' => [
                $userId => ['purchase_order' => 100000_00], // User has higher limit
            ],
            'thresholds' => [],
        ];

        $this->settingsManager
            ->method('getTenantSetting')
            ->willReturn(json_encode($savedConfig));

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($userId);

        $this->userQuery
            ->method('findById')
            ->willReturn($user);

        $this->userQuery
            ->method('getUserRoles')
            ->willReturn(['manager']);

        $authority = $this->manager->getUserAuthority($userId);

        $this->assertTrue($authority->hasOverrides);
        $this->assertSame(100000_00, $authority->limits['purchase_order']);
    }

    /**
     * Test check approval limit within limit.
     */
    public function test_check_approval_limit_within_limit(): void
    {
        $userId = 'user-approver';
        $amountCents = 15000_00; // $15,000

        $savedConfig = [
            'default_limits' => ['purchase_order' => 5000_00],
            'role_limits' => ['approver' => ['purchase_order' => 25000_00]],
            'department_limits' => [],
            'user_overrides' => [],
            'thresholds' => [],
        ];

        $this->settingsManager
            ->method('getTenantSetting')
            ->willReturn(json_encode($savedConfig));

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($userId);

        $this->userQuery
            ->method('findById')
            ->willReturn($user);

        $this->userQuery
            ->method('getUserRoles')
            ->willReturn(['approver']);

        $request = new ApprovalLimitCheckRequest(
            tenantId: self::TENANT_ID,
            userId: $userId,
            documentType: 'purchase_order',
            amountCents: $amountCents,
        );

        $result = $this->manager->checkApprovalLimit($request);

        $this->assertInstanceOf(ApprovalLimitCheckResult::class, $result);
        $this->assertTrue($result->isWithinLimit);
        $this->assertSame(25000_00, $result->effectiveLimitCents);
    }

    /**
     * Test check approval limit exceeds limit.
     */
    public function test_check_approval_limit_exceeds_limit(): void
    {
        $userId = 'user-clerk';
        $amountCents = 50000_00; // $50,000

        $savedConfig = [
            'default_limits' => ['purchase_order' => 5000_00],
            'role_limits' => ['clerk' => ['purchase_order' => 10000_00]],
            'department_limits' => [],
            'user_overrides' => [],
            'thresholds' => [],
        ];

        $this->settingsManager
            ->method('getTenantSetting')
            ->willReturn(json_encode($savedConfig));

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($userId);

        $this->userQuery
            ->method('findById')
            ->willReturn($user);

        $this->userQuery
            ->method('getUserRoles')
            ->willReturn(['clerk']);

        $request = new ApprovalLimitCheckRequest(
            tenantId: self::TENANT_ID,
            userId: $userId,
            documentType: 'purchase_order',
            amountCents: $amountCents,
        );

        $result = $this->manager->checkApprovalLimit($request);

        $this->assertFalse($result->isWithinLimit);
        $this->assertTrue($result->escalationRequired);
        $this->assertSame(40000_00, $result->exceedanceAmountCents);
    }

    /**
     * Test check approval limit with no authority.
     */
    public function test_check_approval_limit_no_authority(): void
    {
        $userId = 'user-viewer';
        $amountCents = 1000_00;

        $savedConfig = [
            'default_limits' => [],
            'role_limits' => [],
            'department_limits' => [],
            'user_overrides' => [],
            'thresholds' => [],
        ];

        $this->settingsManager
            ->method('getTenantSetting')
            ->willReturn(json_encode($savedConfig));

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($userId);

        $this->userQuery
            ->method('findById')
            ->willReturn($user);

        $this->userQuery
            ->method('getUserRoles')
            ->willReturn(['viewer']); // Role with no limits defined

        $request = new ApprovalLimitCheckRequest(
            tenantId: self::TENANT_ID,
            userId: $userId,
            documentType: 'purchase_order',
            amountCents: $amountCents,
        );

        $result = $this->manager->checkApprovalLimit($request);

        $this->assertFalse($result->isWithinLimit);
        $this->assertSame('no_authority', $result->limitSource);
    }

    /**
     * Test get role limits.
     */
    public function test_get_role_limits(): void
    {
        $roleName = 'manager';

        $savedConfig = [
            'default_limits' => ['purchase_order' => 5000_00],
            'role_limits' => [
                'manager' => [
                    'purchase_order' => 50000_00,
                    'vendor_invoice' => 30000_00,
                ],
            ],
            'department_limits' => [],
            'user_overrides' => [],
            'thresholds' => [],
        ];

        $this->settingsManager
            ->method('getTenantSetting')
            ->willReturn(json_encode($savedConfig));

        $limits = $this->manager->getRoleLimits(self::TENANT_ID, $roleName);

        $this->assertIsArray($limits);
        $this->assertSame(50000_00, $limits['purchase_order']);
        $this->assertSame(30000_00, $limits['vendor_invoice']);
    }

    /**
     * Test set role limits.
     */
    public function test_set_role_limits(): void
    {
        $roleName = 'supervisor';
        $limits = [
            'purchase_order' => 35000_00,
            'vendor_invoice' => 20000_00,
        ];

        $savedConfig = [
            'default_limits' => ['purchase_order' => 5000_00],
            'role_limits' => [],
            'department_limits' => [],
            'user_overrides' => [],
            'thresholds' => [],
        ];

        $this->settingsManager
            ->method('getTenantSetting')
            ->willReturn(json_encode($savedConfig));

        $this->settingsManager
            ->expects($this->once())
            ->method('setTenantSetting')
            ->with(
                self::TENANT_ID,
                'procurement.approval_limits',
                $this->callback(function ($value) use ($limits) {
                    $decoded = json_decode($value, true);
                    return $decoded['role_limits']['supervisor'] === $limits;
                }),
            );

        $this->manager->setRoleLimits(self::TENANT_ID, $roleName, $limits);
    }

    /**
     * Test set invalid limit value throws exception.
     */
    public function test_set_invalid_limit_value_throws_exception(): void
    {
        $this->expectException(ApprovalLimitsException::class);
        $this->expectExceptionMessage('Invalid limit value');

        $savedConfig = [
            'default_limits' => [],
            'role_limits' => [],
            'department_limits' => [],
            'user_overrides' => [],
            'thresholds' => [],
        ];

        $this->settingsManager
            ->method('getTenantSetting')
            ->willReturn(json_encode($savedConfig));

        $this->manager->setRoleLimits(self::TENANT_ID, 'manager', [
            'purchase_order' => -1000_00, // Invalid negative amount
        ]);
    }

    /**
     * Test get department limits.
     */
    public function test_get_department_limits(): void
    {
        $departmentId = 'dept-engineering';

        $savedConfig = [
            'default_limits' => ['purchase_order' => 5000_00],
            'role_limits' => [],
            'department_limits' => [
                'dept-engineering' => ['purchase_order' => 75000_00],
            ],
            'user_overrides' => [],
            'thresholds' => [],
        ];

        $this->settingsManager
            ->method('getTenantSetting')
            ->willReturn(json_encode($savedConfig));

        $limits = $this->manager->getDepartmentLimits(self::TENANT_ID, $departmentId);

        $this->assertIsArray($limits);
        $this->assertSame(75000_00, $limits['purchase_order']);
    }

    /**
     * Test get user overrides.
     */
    public function test_get_user_overrides(): void
    {
        $userId = 'user-exec';

        $savedConfig = [
            'default_limits' => ['purchase_order' => 5000_00],
            'role_limits' => [],
            'department_limits' => [],
            'user_overrides' => [
                'user-exec' => ['purchase_order' => 500000_00],
            ],
            'thresholds' => [],
        ];

        $this->settingsManager
            ->method('getTenantSetting')
            ->willReturn(json_encode($savedConfig));

        $overrides = $this->manager->getUserOverrides(self::TENANT_ID, $userId);

        $this->assertIsArray($overrides);
        $this->assertSame(500000_00, $overrides['purchase_order']);
    }

    /**
     * Test set user overrides.
     */
    public function test_set_user_overrides(): void
    {
        $userId = 'user-special';
        $overrides = ['purchase_order' => 150000_00];

        $savedConfig = [
            'default_limits' => ['purchase_order' => 5000_00],
            'role_limits' => [],
            'department_limits' => [],
            'user_overrides' => [],
            'thresholds' => [],
        ];

        $this->settingsManager
            ->method('getTenantSetting')
            ->willReturn(json_encode($savedConfig));

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($userId);

        $this->userQuery
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $this->settingsManager
            ->expects($this->once())
            ->method('setTenantSetting');

        $this->manager->setUserOverrides(self::TENANT_ID, $userId, $overrides);
    }

    /**
     * Test set user overrides for non-existent user throws exception.
     */
    public function test_set_user_overrides_non_existent_user_throws_exception(): void
    {
        $this->expectException(ApprovalLimitsException::class);
        $this->expectExceptionMessage('User not found');

        $savedConfig = [
            'default_limits' => [],
            'role_limits' => [],
            'department_limits' => [],
            'user_overrides' => [],
            'thresholds' => [],
        ];

        $this->settingsManager
            ->method('getTenantSetting')
            ->willReturn(json_encode($savedConfig));

        $this->userQuery
            ->method('findById')
            ->willReturn(null);

        $this->manager->setUserOverrides(self::TENANT_ID, 'non-existent', ['purchase_order' => 1000_00]);
    }

    /**
     * Test reset to defaults.
     */
    public function test_reset_to_defaults(): void
    {
        $this->settingsManager
            ->expects($this->once())
            ->method('setTenantSetting')
            ->with(
                self::TENANT_ID,
                'procurement.approval_limits',
                $this->callback(function ($value) {
                    $decoded = json_decode($value, true);
                    // Should be default config
                    return isset($decoded['default_limits']['purchase_order'])
                        && $decoded['default_limits']['purchase_order'] === 10000_00;
                }),
            );

        $this->manager->resetToDefaults(self::TENANT_ID);
    }
}
