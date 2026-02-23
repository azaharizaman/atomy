<?php

declare(strict_types=1);

namespace Tests\Unit\Orchestrators\TenantOperations\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\TenantOperations\Services\TenantValidationService;
use Nexus\TenantOperations\DTOs\TenantValidationResult;
use Nexus\TenantOperations\DTOs\ModulesValidationRequest;
use Nexus\TenantOperations\DTOs\ConfigurationValidationRequest;
use Nexus\TenantOperations\Rules\TenantStatusCheckerInterface;
use Nexus\TenantOperations\Rules\ModuleCheckerInterface;
use Nexus\TenantOperations\Rules\ConfigurationCheckerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Unit tests for TenantValidationService
 * 
 * Tests tenant validation operations including:
 * - Active tenant validation
 * - Module validation
 * - Configuration validation
 */
final class TenantValidationServiceTest extends TestCase
{
    private TenantStatusCheckerInterface&MockObject $statusChecker;
    private ModuleCheckerInterface&MockObject $moduleChecker;
    private ConfigurationCheckerInterface&MockObject $configChecker;
    private LoggerInterface $logger;
    private TenantValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statusChecker = $this->createMock(TenantStatusCheckerInterface::class);
        $this->moduleChecker = $this->createMock(ModuleCheckerInterface::class);
        $this->configChecker = $this->createMock(ConfigurationCheckerInterface::class);
        $this->logger = new NullLogger();

        $this->service = new TenantValidationService(
            $this->statusChecker,
            $this->moduleChecker,
            $this->configChecker,
            $this->logger
        );
    }

    // =========================================================================
    // Tests for validateActive()
    // =========================================================================

    public function testValidateActive_WhenTenantIsActive_ReturnsValidResult(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $this->statusChecker
            ->expects($this->once())
            ->method('isActive')
            ->with($tenantId)
            ->willReturn(true);

        // Act
        $result = $this->service->validateActive($tenantId);

        // Assert
        $this->assertInstanceOf(TenantValidationResult::class, $result);
        $this->assertTrue($result->valid, 'Expected validation to pass for active tenant');
        $this->assertSame($tenantId, $result->tenantId);
        $this->assertEmpty($result->errors);
        $this->assertStringContainsString('passed', $result->message);
    }

    public function testValidateActive_WhenTenantIsInactive_ReturnsInvalidResult(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $this->statusChecker
            ->expects($this->once())
            ->method('isActive')
            ->with($tenantId)
            ->willReturn(false);

        // Act
        $result = $this->service->validateActive($tenantId);

        // Assert
        $this->assertInstanceOf(TenantValidationResult::class, $result);
        $this->assertFalse($result->valid, 'Expected validation to fail for inactive tenant');
        $this->assertSame($tenantId, $result->tenantId);
        $this->assertNotEmpty($result->errors);
        $this->assertStringContainsString('not active', $result->message);
        $this->assertSame('tenant_active', $result->errors[0]['rule']);
        $this->assertSame('error', $result->errors[0]['severity']);
    }

    public function testValidateActive_WithEmptyTenantId_ThrowsException(): void
    {
        // Arrange
        $tenantId = '';

        // Expect exception
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->service->validateActive($tenantId);
    }

    // =========================================================================
    // Tests for validateModules()
    // =========================================================================

    public function testValidateModules_WhenAllModulesEnabled_ReturnsValidResult(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new ModulesValidationRequest(
            tenantId: $tenantId,
            requiredModules: ['finance', 'hr'],
            requestedBy: 'admin@example.com'
        );

        $this->moduleChecker
            ->expects($this->exactly(2))
            ->method('isModuleEnabled')
            ->willReturnMap([
                [$tenantId, 'finance', true],
                [$tenantId, 'hr', true],
            ]);

        // Act
        $result = $this->service->validateModules($request);

        // Assert
        $this->assertInstanceOf(TenantValidationResult::class, $result);
        $this->assertTrue($result->valid);
        $this->assertEmpty($result->errors);
    }

    public function testValidateModules_WhenSomeModulesDisabled_ReturnsInvalidResult(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new ModulesValidationRequest(
            tenantId: $tenantId,
            requiredModules: ['finance', 'hr', 'sales'],
            requestedBy: 'admin@example.com'
        );

        $this->moduleChecker
            ->expects($this->exactly(3))
            ->method('isModuleEnabled')
            ->willReturnMap([
                [$tenantId, 'finance', true],
                [$tenantId, 'hr', true],
                [$tenantId, 'sales', false],
            ]);

        // Act
        $result = $this->service->validateModules($request);

        // Assert
        $this->assertInstanceOf(TenantValidationResult::class, $result);
        $this->assertFalse($result->valid);
        $this->assertCount(1, $result->errors);
        $this->assertStringContainsString('sales', $result->errors[0]['message']);
        $this->assertSame('tenant_modules_enabled', $result->errors[0]['rule']);
    }

    public function testValidateModules_WhenAllModulesDisabled_ReturnsMultipleErrors(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new ModulesValidationRequest(
            tenantId: $tenantId,
            requiredModules: ['finance', 'hr', 'sales'],
        );

        $this->moduleChecker
            ->expects($this->exactly(3))
            ->method('isModuleEnabled')
            ->willReturn(false);

        // Act
        $result = $this->service->validateModules($request);

        // Assert
        $this->assertFalse($result->valid);
        $this->assertCount(3, $result->errors);
    }

    public function testValidateModules_WithEmptyRequiredModules_ReturnsValidResult(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new ModulesValidationRequest(
            tenantId: $tenantId,
            requiredModules: [],
        );

        $this->moduleChecker
            ->expects($this->never())
            ->method('isModuleEnabled');

        // Act
        $result = $this->service->validateModules($request);

        // Assert
        $this->assertTrue($result->valid);
    }

    // =========================================================================
    // Tests for validateConfiguration()
    // =========================================================================

    public function testValidateConfiguration_WhenAllConfigsExist_ReturnsValidResult(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new ConfigurationValidationRequest(
            tenantId: $tenantId,
            requiredConfigs: ['currency', 'timezone'],
        );

        $this->configChecker
            ->expects($this->exactly(2))
            ->method('configurationExists')
            ->willReturnMap([
                [$tenantId, 'currency', true],
                [$tenantId, 'timezone', true],
            ]);

        // Act
        $result = $this->service->validateConfiguration($request);

        // Assert
        $this->assertTrue($result->valid);
        $this->assertEmpty($result->errors);
    }

    public function testValidateConfiguration_WhenSomeConfigsMissing_ReturnsInvalidResult(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new ConfigurationValidationRequest(
            tenantId: $tenantId,
            requiredConfigs: ['currency', 'timezone', 'fiscal_year'],
        );

        $this->configChecker
            ->expects($this->exactly(3))
            ->method('configurationExists')
            ->willReturnMap([
                [$tenantId, 'currency', true],
                [$tenantId, 'timezone', true],
                [$tenantId, 'fiscal_year', false],
            ]);

        // Act
        $result = $this->service->validateConfiguration($request);

        // Assert
        $this->assertFalse($result->valid);
        $this->assertCount(1, $result->errors);
        $this->assertStringContainsString('fiscal_year', $result->errors[0]['message']);
    }

    public function testValidateConfiguration_WhenAllConfigsMissing_ReturnsMultipleErrors(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new ConfigurationValidationRequest(
            tenantId: $tenantId,
            requiredConfigs: ['currency', 'timezone'],
        );

        $this->configChecker
            ->expects($this->exactly(2))
            ->method('configurationExists')
            ->willReturn(false);

        // Act
        $result = $this->service->validateConfiguration($request);

        // Assert
        $this->assertFalse($result->valid);
        $this->assertCount(2, $result->errors);
    }

    // =========================================================================
    // Tests for isTenantActive()
    // =========================================================================

    public function testIsTenantActive_WhenActive_ReturnsTrue(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $this->statusChecker
            ->expects($this->once())
            ->method('isActive')
            ->with($tenantId)
            ->willReturn(true);

        // Act
        $result = $this->service->isTenantActive($tenantId);

        // Assert
        $this->assertTrue($result);
    }

    public function testIsTenantActive_WhenInactive_ReturnsFalse(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $this->statusChecker
            ->expects($this->once())
            ->method('isActive')
            ->with($tenantId)
            ->willReturn(false);

        // Act
        $result = $this->service->isTenantActive($tenantId);

        // Assert
        $this->assertFalse($result);
    }

    // =========================================================================
    // Tests for hasModuleEnabled()
    // =========================================================================

    public function testHasModuleEnabled_WhenEnabled_ReturnsTrue(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $moduleKey = 'finance';
        $this->moduleChecker
            ->expects($this->once())
            ->method('isModuleEnabled')
            ->with($tenantId, $moduleKey)
            ->willReturn(true);

        // Act
        $result = $this->service->hasModuleEnabled($tenantId, $moduleKey);

        // Assert
        $this->assertTrue($result);
    }

    public function testHasModuleEnabled_WhenDisabled_ReturnsFalse(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $moduleKey = 'sales';
        $this->moduleChecker
            ->expects($this->once())
            ->method('isModuleEnabled')
            ->with($tenantId, $moduleKey)
            ->willReturn(false);

        // Act
        $result = $this->service->hasModuleEnabled($tenantId, $moduleKey);

        // Assert
        $this->assertFalse($result);
    }

    // =========================================================================
    // Edge Case Tests
    // =========================================================================

    public function testValidateActive_WithNullTenantId_ThrowsException(): void
    {
        // This test verifies strict type handling
        $this->expectException(\TypeError::class);
        
        // We need to cast null to string to pass type check
        $this->service->validateActive(null);
    }

    public function testValidateModules_WithNullTenantIdInRequest_ThrowsException(): void
    {
        // This test verifies strict type handling
        $this->expectException(\TypeError::class);
        
        // Passing null as tenantId
        $request = new ModulesValidationRequest(
            tenantId: null,
            requiredModules: [],
        );
    }

    public function testValidateConfiguration_WithNullTenantIdInRequest_ThrowsException(): void
    {
        // This test verifies strict type handling
        $this->expectException(\TypeError::class);
        
        // Passing null as tenantId
        $request = new ConfigurationValidationRequest(
            tenantId: null,
            requiredConfigs: [],
        );
    }

    public function testIsTenantActive_WithWhitespaceOnlyTenantId_CallsChecker(): void
    {
        // Arrange - whitespace-only string is valid but likely indicates an issue
        $tenantId = '   ';
        $this->statusChecker
            ->expects($this->once())
            ->method('isActive')
            ->with($tenantId)
            ->willReturn(false);

        // Act
        $result = $this->service->isTenantActive($tenantId);

        // Assert
        $this->assertFalse($result);
    }
}
