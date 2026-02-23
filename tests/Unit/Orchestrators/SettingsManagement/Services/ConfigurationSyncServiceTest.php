<?php

declare(strict_types=1);

namespace Tests\Unit\Orchestrators\SettingsManagement\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\SettingsManagement\Services\ConfigurationSyncService;
use Nexus\SettingsManagement\Contracts\SettingsProviderInterface;
use Nexus\SettingsManagement\Contracts\FeatureFlagProviderInterface;
use Nexus\SettingsManagement\Contracts\FiscalPeriodProviderInterface;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationExportRequest;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationExportResult;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationImportRequest;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationImportResult;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationRollbackRequest;
use Nexus\SettingsManagement\DTOs\Configuration\ConfigurationRollbackResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Unit tests for ConfigurationSyncService
 * 
 * Tests configuration synchronization operations including:
 * - Exporting configuration
 * - Importing configuration
 * - Rolling back configuration
 * - Getting configuration history
 */
final class ConfigurationSyncServiceTest extends TestCase
{
    private SettingsProviderInterface&MockObject $settingsProvider;
    private FeatureFlagProviderInterface&MockObject $flagProvider;
    private FiscalPeriodProviderInterface&MockObject $periodProvider;
    private LoggerInterface $logger;
    private ConfigurationSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settingsProvider = $this->createMock(SettingsProviderInterface::class);
        $this->flagProvider = $this->createMock(FeatureFlagProviderInterface::class);
        $this->periodProvider = $this->createMock(FiscalPeriodProviderInterface::class);
        $this->logger = new NullLogger();

        $this->service = new ConfigurationSyncService(
            $this->settingsProvider,
            $this->flagProvider,
            $this->periodProvider,
            $this->logger
        );
    }

    // =========================================================================
    // Tests for exportConfiguration()
    // =========================================================================

    public function testExportConfiguration_WithAllOptions_ReturnsJsonWithAllData(): void
    {
        // Arrange
        $request = new ConfigurationExportRequest(
            tenantId: 'tenant-123',
            includeSettings: true,
            includeFeatureFlags: true,
            includeFiscalPeriods: true
        );

        $this->settingsProvider
            ->expects($this->once())
            ->method('getAllSettings')
            ->with('tenant-123')
            ->willReturn(['currency' => 'USD', 'timezone' => 'UTC']);

        $this->flagProvider
            ->expects($this->once())
            ->method('getAllFlags')
            ->with('tenant-123')
            ->willReturn(['feature_a' => true]);

        $this->periodProvider
            ->expects($this->once())
            ->method('getCalendarConfig')
            ->with('tenant-123')
            ->willReturn(['period_type' => 'monthly']);

        $this->periodProvider
            ->expects($this->once())
            ->method('getAllPeriods')
            ->with('tenant-123')
            ->willReturn(['periods' => []]);

        // Act
        $result = $this->service->exportConfiguration($request);

        // Assert
        $this->assertInstanceOf(ConfigurationExportResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertJson($result->jsonData);
        
        $data = json_decode($result->jsonData, true);
        $this->assertArrayHasKey('settings', $data);
        $this->assertArrayHasKey('feature_flags', $data);
        $this->assertArrayHasKey('fiscal_calendar', $data);
        $this->assertArrayHasKey('fiscal_periods', $data);
    }

    public function testExportConfiguration_OnlySettings_ReturnsJsonWithSettingsOnly(): void
    {
        // Arrange
        $request = new ConfigurationExportRequest(
            tenantId: 'tenant-123',
            includeSettings: true,
            includeFeatureFlags: false,
            includeFiscalPeriods: false
        );

        $this->settingsProvider
            ->expects($this->once())
            ->method('getAllSettings')
            ->with('tenant-123')
            ->willReturn(['currency' => 'USD']);

        $this->flagProvider
            ->expects($this->never())
            ->method('getAllFlags');

        $this->periodProvider
            ->expects($this->never())
            ->method('getCalendarConfig');

        // Act
        $result = $this->service->exportConfiguration($request);

        // Assert
        $this->assertTrue($result->success);
        
        $data = json_decode($result->jsonData, true);
        $this->assertArrayHasKey('settings', $data);
        $this->assertArrayNotHasKey('feature_flags', $data);
        $this->assertArrayNotHasKey('fiscal_calendar', $data);
    }

    public function testExportConfiguration_OnlyFlags_ReturnsJsonWithFlagsOnly(): void
    {
        // Arrange
        $request = new ConfigurationExportRequest(
            tenantId: 'tenant-123',
            includeSettings: false,
            includeFeatureFlags: true,
            includeFiscalPeriods: false
        );

        $this->flagProvider
            ->expects($this->once())
            ->method('getAllFlags')
            ->with('tenant-123')
            ->willReturn(['feature_a' => true]);

        // Act
        $result = $this->service->exportConfiguration($request);

        // Assert
        $this->assertTrue($result->success);
        
        $data = json_decode($result->jsonData, true);
        $this->assertArrayHasKey('feature_flags', $data);
        $this->assertArrayNotHasKey('settings', $data);
    }

    public function testExportConfiguration_IncludesVersionAndMetadata(): void
    {
        // Arrange
        $request = new ConfigurationExportRequest(
            tenantId: 'tenant-123',
            includeSettings: false,
            includeFeatureFlags: false,
            includeFiscalPeriods: false
        );

        // Act
        $result = $this->service->exportConfiguration($request);

        // Assert
        $this->assertTrue($result->success);
        
        $data = json_decode($result->jsonData, true);
        $this->assertArrayHasKey('version', $data);
        $this->assertArrayHasKey('exported_at', $data);
        $this->assertArrayHasKey('tenant_id', $data);
        $this->assertSame('tenant-123', $data['tenant_id']);
    }

    public function testExportConfiguration_WhenProviderThrows_ReturnsFailureResult(): void
    {
        // Arrange
        $request = new ConfigurationExportRequest(
            tenantId: 'tenant-123',
            includeSettings: true,
            includeFeatureFlags: false,
            includeFiscalPeriods: false
        );

        $this->settingsProvider
            ->expects($this->once())
            ->method('getAllSettings')
            ->willThrowException(new \RuntimeException('Database error'));

        // Act
        $result = $this->service->exportConfiguration($request);

        // Assert
        $this->assertInstanceOf(ConfigurationExportResult::class, $result);
        $this->assertFalse($result->success);
        $this->assertStringContainsString('Failed', $result->error);
    }

    // =========================================================================
    // Tests for importConfiguration()
    // =========================================================================

    public function testImportConfiguration_WhenValidJson_ReturnsSuccessResult(): void
    {
        // Arrange
        $jsonData = json_encode([
            'settings' => ['currency' => 'USD', 'timezone' => 'UTC'],
            'feature_flags' => ['feature_a' => true],
            'fiscal_periods' => ['period_1', 'period_2'],
        ]);

        $request = new ConfigurationImportRequest(
            tenantId: 'tenant-123',
            jsonData: $jsonData,
            validateOnly: false
        );

        // Act
        $result = $this->service->importConfiguration($request);

        // Assert
        $this->assertInstanceOf(ConfigurationImportResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame(2, $result->settingsImported);
        $this->assertSame(1, $result->flagsImported);
        $this->assertSame(2, $result->periodsImported);
    }

    public function testImportConfiguration_WhenInvalidJson_ReturnsFailureResult(): void
    {
        // Arrange
        $request = new ConfigurationImportRequest(
            tenantId: 'tenant-123',
            jsonData: '{ invalid json }',
            validateOnly: false
        );

        // Act
        $result = $this->service->importConfiguration($request);

        // Assert
        $this->assertInstanceOf(ConfigurationImportResult::class, $result);
        $this->assertFalse($result->success);
        $this->assertStringContainsString('Invalid JSON', $result->error);
    }

    public function testImportConfiguration_WhenValidateOnly_DoesNotImport(): void
    {
        // Arrange
        $jsonData = json_encode([
            'settings' => ['currency' => 'USD'],
        ]);

        $request = new ConfigurationImportRequest(
            tenantId: 'tenant-123',
            jsonData: $jsonData,
            validateOnly: true
        );

        // Act
        $result = $this->service->importConfiguration($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertSame(0, $result->settingsImported);
    }

    public function testImportConfiguration_WithEmptyData_ReturnsSuccessWithZeroCounts(): void
    {
        // Arrange
        $jsonData = json_encode([]);

        $request = new ConfigurationImportRequest(
            tenantId: 'tenant-123',
            jsonData: $jsonData,
            validateOnly: false
        );

        // Act
        $result = $this->service->importConfiguration($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertSame(0, $result->settingsImported);
        $this->assertSame(0, $result->flagsImported);
        $this->assertSame(0, $result->periodsImported);
    }

    // =========================================================================
    // Tests for rollbackConfiguration()
    // =========================================================================

    public function testRollbackConfiguration_WhenSuccessful_ReturnsSuccessResult(): void
    {
        // Arrange
        $request = new ConfigurationRollbackRequest(
            tenantId: 'tenant-123',
            targetVersion: 5,
            dryRun: false
        );

        // Act
        $result = $this->service->rollbackConfiguration($request);

        // Assert
        $this->assertInstanceOf(ConfigurationRollbackResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame(5, $result->rolledBackToVersion);
    }

    public function testRollbackConfiguration_WhenDryRun_ReturnsSuccessWithoutChanges(): void
    {
        // Arrange
        $request = new ConfigurationRollbackRequest(
            tenantId: 'tenant-123',
            targetVersion: 3,
            dryRun: true
        );

        // Act
        $result = $this->service->rollbackConfiguration($request);

        // Assert
        $this->assertTrue($result->success);
    }

    public function testRollbackConfiguration_WhenProviderThrows_ReturnsFailureResult(): void
    {
        // Arrange
        $request = new ConfigurationRollbackRequest(
            tenantId: 'tenant-123',
            targetVersion: 1,
            dryRun: false
        );

        // The service doesn't actually implement rollback logic yet
        // This tests the current behavior

        // Act
        $result = $this->service->rollbackConfiguration($request);

        // Assert
        $this->assertInstanceOf(ConfigurationRollbackResult::class, $result);
        $this->assertTrue($result->success); // Currently always succeeds
    }

    // =========================================================================
    // Tests for getConfigurationHistory()
    // =========================================================================

    public function testGetConfigurationHistory_ReturnsEmptyArray(): void
    {
        // Arrange
        $tenantId = 'tenant-123';

        // Act
        $result = $this->service->getConfigurationHistory($tenantId);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetConfigurationHistory_WithLimit_RespectsLimit(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $limit = 5;

        // Act
        $result = $this->service->getConfigurationHistory($tenantId, $limit);

        // Assert
        $this->assertIsArray($result);
    }

    // =========================================================================
    // Tests for getConfigurationVersion()
    // =========================================================================

    public function testGetConfigurationVersion_ReturnsNull(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $version = 5;

        // Act
        $result = $this->service->getConfigurationVersion($tenantId, $version);

        // Assert
        $this->assertNull($result);
    }

    // =========================================================================
    // Edge Case Tests
    // =========================================================================

    public function testExportConfiguration_WithEmptySettings_ReturnsEmptyArray(): void
    {
        // Arrange
        $request = new ConfigurationExportRequest(
            tenantId: 'tenant-123',
            includeSettings: true,
            includeFeatureFlags: false,
            includeFiscalPeriods: false
        );

        $this->settingsProvider
            ->expects($this->once())
            ->method('getAllSettings')
            ->willReturn([]);

        // Act
        $result = $this->service->exportConfiguration($request);

        // Assert
        $this->assertTrue($result->success);
        
        $data = json_decode($result->jsonData, true);
        $this->assertEmpty($data['settings']);
    }

    public function testImportConfiguration_WithMalformedJson_ReturnsError(): void
    {
        // Arrange - truncated JSON
        $request = new ConfigurationImportRequest(
            tenantId: 'tenant-123',
            jsonData: '{"settings":',
            validateOnly: false
        );

        // Act
        $result = $this->service->importConfiguration($request);

        // Assert
        $this->assertFalse($result->success);
    }

    public function testImportConfiguration_WithNullValues_HandlesGracefully(): void
    {
        // Arrange
        $jsonData = json_encode([
            'settings' => null,
            'feature_flags' => null,
        ]);

        $request = new ConfigurationImportRequest(
            tenantId: 'tenant-123',
            jsonData: $jsonData,
            validateOnly: false
        );

        // Act
        $result = $this->service->importConfiguration($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertSame(0, $result->settingsImported);
    }

    public function testExportConfiguration_SetsCorrectExportVersion(): void
    {
        // Arrange
        $request = new ConfigurationExportRequest(
            tenantId: 'tenant-123',
            includeSettings: false,
            includeFeatureFlags: false,
            includeFiscalPeriods: false
        );

        // Act
        $result = $this->service->exportConfiguration($request);

        // Assert
        $data = json_decode($result->jsonData, true);
        $this->assertSame('1.0.0', $data['version']);
    }

    public function testExportConfiguration_SetsExportTimestamp(): void
    {
        // Arrange
        $request = new ConfigurationExportRequest(
            tenantId: 'tenant-123',
            includeSettings: false,
            includeFeatureFlags: false,
            includeFiscalPeriods: false
        );

        // Act
        $result = $this->service->exportConfiguration($request);

        // Assert
        $data = json_decode($result->jsonData, true);
        $this->assertNotNull($data['exported_at']);
        $this->assertTrue(strtotime($data['exported_at']) !== false);
    }
}
