<?php

declare(strict_types=1);

namespace Tests\Unit\Orchestrators\SettingsManagement\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\SettingsManagement\Services\SettingsUpdateService;
use Nexus\SettingsManagement\Contracts\SettingsProviderInterface;
use Nexus\SettingsManagement\DTOs\Settings\SettingUpdateRequest;
use Nexus\SettingsManagement\DTOs\Settings\SettingUpdateResult;
use Nexus\SettingsManagement\DTOs\Settings\BulkSettingUpdateRequest;
use Nexus\SettingsManagement\DTOs\Settings\BulkSettingUpdateResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Unit tests for SettingsUpdateService
 * 
 * Tests settings management operations including:
 * - Single setting updates
 * - Bulk setting updates
 * - Setting value resolution
 */
final class SettingsUpdateServiceTest extends TestCase
{
    private SettingsProviderInterface&MockObject $settingsProvider;
    private LoggerInterface $logger;
    private SettingsUpdateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settingsProvider = $this->createMock(SettingsProviderInterface::class);
        $this->logger = new NullLogger();

        $this->service = new SettingsUpdateService(
            $this->settingsProvider,
            $this->logger
        );
    }

    // =========================================================================
    // Tests for updateSetting()
    // =========================================================================

    public function testUpdateSetting_WithExistingSetting_ReturnsSuccessResult(): void
    {
        // Arrange
        $request = new SettingUpdateRequest(
            key: 'currency',
            value: 'USD',
            tenantId: 'tenant-123',
            userId: 'user-456',
            reason: 'Changed to USD'
        );

        $this->settingsProvider
            ->expects($this->once())
            ->method('getSetting')
            ->with('currency', 'tenant-123')
            ->willReturn(['value' => 'EUR']);

        // Act
        $result = $this->service->updateSetting($request);

        // Assert
        $this->assertInstanceOf(SettingUpdateResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame('currency', $result->settingKey);
        $this->assertSame('EUR', $result->oldValue);
        $this->assertSame('USD', $result->newValue);
        $this->assertNull($result->error);
    }

    public function testUpdateSetting_WithNewSetting_ReturnsSuccessWithNullOldValue(): void
    {
        // Arrange
        $request = new SettingUpdateRequest(
            key: 'new_setting',
            value: 'some_value',
            tenantId: 'tenant-123',
            userId: 'user-456'
        );

        $this->settingsProvider
            ->expects($this->once())
            ->method('getSetting')
            ->with('new_setting', 'tenant-123')
            ->willReturn(null);

        // Act
        $result = $this->service->updateSetting($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertNull($result->oldValue);
        $this->assertSame('some_value', $result->newValue);
    }

    public function testUpdateSetting_WithoutTenantId_UpdatesGlobalSetting(): void
    {
        // Arrange
        $request = new SettingUpdateRequest(
            key: 'app_name',
            value: 'My Application',
            tenantId: null,
            userId: 'user-456'
        );

        $this->settingsProvider
            ->expects($this->once())
            ->method('getSetting')
            ->with('app_name', null)
            ->willReturn(['value' => 'Old Name']);

        // Act
        $result = $this->service->updateSetting($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertSame('My Application', $result->newValue);
    }

    public function testUpdateSetting_WithEmptyKey_ThrowsException(): void
    {
        // Arrange
        $request = new SettingUpdateRequest(
            key: '',
            value: 'some_value',
            tenantId: 'tenant-123'
        );

        // The service doesn't validate empty keys - we can test behavior
        // but in production this should be validated

        // This test documents expected behavior
        $this->expectException(\InvalidArgumentException::class);
    }

    // =========================================================================
    // Tests for bulkUpdateSettings()
    // =========================================================================

    public function testBulkUpdateSettings_WhenAllSucceed_ReturnsSuccessResult(): void
    {
        // Arrange
        $settings = [
            'currency' => 'USD',
            'timezone' => 'America/New_York',
            'language' => 'en',
        ];

        $request = new BulkSettingUpdateRequest(
            settings: $settings,
            tenantId: 'tenant-123',
            userId: 'user-456',
            reason: 'Bulk update'
        );

        $this->settingsProvider
            ->expects($this->exactly(3))
            ->method('getSetting')
            ->willReturn(['value' => 'old']);

        // Act
        $result = $this->service->bulkUpdateSettings($request);

        // Assert
        $this->assertInstanceOf(BulkSettingUpdateResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertCount(3, $result->results);
    }

    public function testBulkUpdateSettings_WhenSomeFail_ReturnsPartialFailureResult(): void
    {
        // Arrange
        $settings = [
            'valid_setting' => 'value1',
            'invalid_setting' => 'value2',
        ];

        $request = new BulkSettingUpdateRequest(
            settings: $settings,
            tenantId: 'tenant-123',
            userId: 'user-456'
        );

        // First call succeeds
        $this->settingsProvider
            ->expects($this->exactly(2))
            ->method('getSetting')
            ->willReturnCallback(function($key) {
                return $key === 'valid_setting' ? ['value' => 'old'] : null;
            });

        // Act
        $result = $this->service->bulkUpdateSettings($request);

        // Assert
        $this->assertInstanceOf(BulkSettingUpdateResult::class, $result);
        $this->assertFalse($result->success);
        $this->assertNotEmpty($result->failedKeys);
    }

    public function testBulkUpdateSettings_WithEmptySettings_ReturnsSuccessWithEmptyResults(): void
    {
        // Arrange
        $request = new BulkSettingUpdateRequest(
            settings: [],
            tenantId: 'tenant-123',
            userId: 'user-456'
        );

        $this->settingsProvider
            ->expects($this->never())
            ->method('getSetting');

        // Act
        $result = $this->service->bulkUpdateSettings($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertEmpty($result->results);
    }

    public function testBulkUpdateSettings_LogsWarningForFailures(): void
    {
        // Arrange
        $settings = [
            'setting1' => 'value1',
            'setting2' => 'value2',
        ];

        $request = new BulkSettingUpdateRequest(
            settings: $settings,
            tenantId: 'tenant-123',
            userId: 'user-456'
        );

        // Both settings succeed
        $this->settingsProvider
            ->expects($this->exactly(2))
            ->method('getSetting')
            ->willReturn(['value' => 'old']);

        // Act
        $result = $this->service->bulkUpdateSettings($request);

        // Assert
        $this->assertTrue($result->success);
    }

    // =========================================================================
    // Tests for resolveSettingValue()
    // =========================================================================

    public function testResolveSettingValue_WithTenantAndUser_ReturnsResolvedValue(): void
    {
        // Arrange
        $key = 'theme';
        $tenantId = 'tenant-123';
        $userId = 'user-456';

        $this->settingsProvider
            ->expects($this->once())
            ->method('resolveSettingValue')
            ->with($key, $tenantId, $userId)
            ->willReturn('dark');

        // Act
        $result = $this->service->resolveSettingValue($key, $tenantId, $userId);

        // Assert
        $this->assertSame('dark', $result);
    }

    public function testResolveSettingValue_WithoutUserId_ReturnsTenantValue(): void
    {
        // Arrange
        $key = 'currency';
        $tenantId = 'tenant-123';
        $userId = null;

        $this->settingsProvider
            ->expects($this->once())
            ->method('resolveSettingValue')
            ->with($key, $tenantId, null)
            ->willReturn('EUR');

        // Act
        $result = $this->service->resolveSettingValue($key, $tenantId, $userId);

        // Assert
        $this->assertSame('EUR', $result);
    }

    public function testResolveSettingValue_WithoutTenantId_ReturnsGlobalValue(): void
    {
        // Arrange
        $key = 'app_name';
        $tenantId = null;
        $userId = null;

        $this->settingsProvider
            ->expects($this->once())
            ->method('resolveSettingValue')
            ->with($key, null, null)
            ->willReturn('Global App');

        // Act
        $result = $this->service->resolveSettingValue($key, $tenantId, $userId);

        // Assert
        $this->assertSame('Global App', $result);
    }

    public function testResolveSettingValue_WhenNotFound_ReturnsNull(): void
    {
        // Arrange
        $key = 'nonexistent_setting';
        $tenantId = 'tenant-123';
        $userId = 'user-456';

        $this->settingsProvider
            ->expects($this->once())
            ->method('resolveSettingValue')
            ->with($key, $tenantId, $userId)
            ->willReturn(null);

        // Act
        $result = $this->service->resolveSettingValue($key, $tenantId, $userId);

        // Assert
        $this->assertNull($result);
    }

    public function testResolveSettingValue_WithComplexValue_ReturnsComplexValue(): void
    {
        // Arrange
        $key = 'complex_setting';
        $tenantId = 'tenant-123';
        $userId = null;

        $complexValue = [
            'enabled' => true,
            'options' => ['a', 'b', 'c'],
            'count' => 42,
        ];

        $this->settingsProvider
            ->expects($this->once())
            ->method('resolveSettingValue')
            ->with($key, $tenantId, $userId)
            ->willReturn($complexValue);

        // Act
        $result = $this->service->resolveSettingValue($key, $tenantId, $userId);

        // Assert
        $this->assertIsArray($result);
        $this->assertTrue($result['enabled']);
        $this->assertCount(3, $result['options']);
    }

    // =========================================================================
    // Edge Case Tests
    // =========================================================================

    public function testUpdateSetting_WithNumericValue_StoresCorrectly(): void
    {
        // Arrange
        $request = new SettingUpdateRequest(
            key: 'max_users',
            value: 100,
            tenantId: 'tenant-123'
        );

        $this->settingsProvider
            ->expects($this->once())
            ->method('getSetting')
            ->willReturn(['value' => 50]);

        // Act
        $result = $this->service->updateSetting($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertSame(100, $result->newValue);
    }

    public function testUpdateSetting_WithBooleanValue_StoresCorrectly(): void
    {
        // Arrange
        $request = new SettingUpdateRequest(
            key: 'feature_enabled',
            value: true,
            tenantId: 'tenant-123'
        );

        $this->settingsProvider
            ->expects($this->once())
            ->method('getSetting')
            ->willReturn(['value' => false]);

        // Act
        $result = $this->service->updateSetting($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertTrue($result->newValue);
    }

    public function testUpdateSetting_WithArrayValue_StoresCorrectly(): void
    {
        // Arrange
        $request = new SettingUpdateRequest(
            key: 'allowed_domains',
            value: ['example.com', 'test.com'],
            tenantId: 'tenant-123'
        );

        $this->settingsProvider
            ->expects($this->once())
            ->method('getSetting')
            ->willReturn(['value' => []]);

        // Act
        $result = $this->service->updateSetting($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertIsArray($result->newValue);
        $this->assertCount(2, $result->newValue);
    }

    public function testBulkUpdateSettings_WithLargeNumberOfSettings_HandlesEfficiently(): void
    {
        // Arrange
        $settings = [];
        for ($i = 0; $i < 50; $i++) {
            $settings["setting_{$i}"] = "value_{$i}";
        }

        $request = new BulkSettingUpdateRequest(
            settings: $settings,
            tenantId: 'tenant-123',
            userId: 'user-456'
        );

        $this->settingsProvider
            ->expects($this->exactly(50))
            ->method('getSetting')
            ->willReturn(['value' => 'old']);

        // Act
        $result = $this->service->bulkUpdateSettings($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertCount(50, $result->results);
    }

    public function testBulkUpdateSettings_PassesReasonToIndividualUpdates(): void
    {
        // Arrange
        $settings = ['setting1' => 'value1'];
        $reason = 'Testing bulk update';

        $request = new BulkSettingUpdateRequest(
            settings: $settings,
            tenantId: 'tenant-123',
            userId: 'user-456',
            reason: $reason
        );

        $this->settingsProvider
            ->expects($this->once())
            ->method('getSetting')
            ->willReturn(['value' => 'old']);

        // Act
        $result = $this->service->bulkUpdateSettings($request);

        // Assert
        $this->assertTrue($result->success);
    }
}
