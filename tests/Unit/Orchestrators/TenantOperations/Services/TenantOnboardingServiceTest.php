<?php

declare(strict_types=1);

namespace Tests\Unit\Orchestrators\TenantOperations\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\TenantOperations\Services\TenantOnboardingService;
use Nexus\TenantOperations\Services\TenantCreatorInterface;
use Nexus\TenantOperations\Services\SettingsInitializerInterface;
use Nexus\TenantOperations\Services\FeatureConfiguratorInterface;
use Nexus\TenantOperations\Services\CompanyCreatorInterface;
use Nexus\TenantOperations\Services\AdminCreatorInterface;
use Nexus\TenantOperations\Services\AuditLoggerInterface;
use Nexus\TenantOperations\DTOs\TenantOnboardingRequest;
use Nexus\TenantOperations\DTOs\TenantOnboardingResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Unit tests for TenantOnboardingService
 * 
 * Tests tenant onboarding operations including:
 * - Creating new tenant
 * - Initializing settings
 * - Configuring features
 * - Creating company structure
 * - Creating admin user
 */
final class TenantOnboardingServiceTest extends TestCase
{
    private TenantCreatorInterface&MockObject $tenantCreator;
    private SettingsInitializerInterface&MockObject $settingsInitializer;
    private FeatureConfiguratorInterface&MockObject $featureConfigurator;
    private CompanyCreatorInterface&MockObject $companyCreator;
    private AdminCreatorInterface&MockObject $adminCreator;
    private AuditLoggerInterface&MockObject $auditLogger;
    private LoggerInterface $logger;
    private TenantOnboardingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantCreator = $this->createMock(TenantCreatorInterface::class);
        $this->settingsInitializer = $this->createMock(SettingsInitializerInterface::class);
        $this->featureConfigurator = $this->createMock(FeatureConfiguratorInterface::class);
        $this->companyCreator = $this->createMock(CompanyCreatorInterface::class);
        $this->adminCreator = $this->createMock(AdminCreatorInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);
        $this->logger = new NullLogger();

        $this->service = new TenantOnboardingService(
            $this->tenantCreator,
            $this->settingsInitializer,
            $this->featureConfigurator,
            $this->companyCreator,
            $this->adminCreator,
            $this->auditLogger,
            $this->logger
        );
    }

    // =========================================================================
    // Tests for onboard()
    // =========================================================================

    public function testOnboard_WhenSuccessful_ReturnsSuccessResult(): void
    {
        // Arrange
        $request = new TenantOnboardingRequest(
            tenantCode: 'acme-corp',
            tenantName: 'Acme Corporation',
            domain: 'acme.example.com',
            adminEmail: 'admin@acme.example.com',
            adminPassword: 'securePassword123!',
            plan: 'professional',
            currency: 'USD',
            timezone: 'America/New_York',
            language: 'en'
        );

        $expectedTenantId = 'tenant-uuid-123';
        $expectedCompanyId = 'company-uuid-456';
        $expectedAdminUserId = 'user-uuid-789';

        $this->tenantCreator
            ->expects($this->once())
            ->method('create')
            ->with('acme-corp', 'Acme Corporation', 'acme.example.com')
            ->willReturn($expectedTenantId);

        $this->settingsInitializer
            ->expects($this->once())
            ->method('initialize')
            ->with($expectedTenantId, $this->isType('array'));

        $this->featureConfigurator
            ->expects($this->once())
            ->method('configure')
            ->with($expectedTenantId, $this->isType('array'));

        $this->companyCreator
            ->expects($this->once())
            ->method('createDefaultStructure')
            ->with(
                tenantId: $expectedTenantId,
                companyName: 'Acme Corporation Default Company'
            )
            ->willReturn($expectedCompanyId);

        $this->adminCreator
            ->expects($this->once())
            ->method('create')
            ->with(
                tenantId: $expectedTenantId,
                email: 'admin@acme.example.com',
                password: 'securePassword123!',
                isAdmin: true
            )
            ->willReturn($expectedAdminUserId);

        $this->auditLogger
            ->expects($this->once())
            ->method('log')
            ->with(
                'tenant.onboarded',
                $expectedTenantId,
                $this->callback(function ($data) use ($expectedAdminUserId, $expectedCompanyId) {
                    return $data['admin_user_id'] === $expectedAdminUserId
                        && $data['company_id'] === $expectedCompanyId;
                })
            );

        // Act
        $result = $this->service->onboard($request);

        // Assert
        $this->assertInstanceOf(TenantOnboardingResult::class, $result);
        $this->assertTrue($result->success, 'Expected onboarding to succeed');
        $this->assertSame($expectedTenantId, $result->tenantId);
        $this->assertSame($expectedAdminUserId, $result->adminUserId);
        $this->assertSame($expectedCompanyId, $result->companyId);
        $this->assertStringContainsString('successfully', $result->message);
    }

    public function testOnboard_WhenTenantCreationFails_ReturnsFailureResult(): void
    {
        // Arrange
        $request = new TenantOnboardingRequest(
            tenantCode: 'acme-corp',
            tenantName: 'Acme Corporation',
            domain: 'acme.example.com',
            adminEmail: 'admin@acme.example.com',
            adminPassword: 'securePassword123!',
            plan: 'professional'
        );

        $this->tenantCreator
            ->expects($this->once())
            ->method('create')
            ->willThrowException(new \RuntimeException('Tenant code already exists'));

        $this->settingsInitializer
            ->expects($this->never())
            ->method('initialize');

        $this->featureConfigurator
            ->expects($this->never())
            ->method('configure');

        $this->companyCreator
            ->expects($this->never())
            ->method('createDefaultStructure');

        $this->adminCreator
            ->expects($this->never())
            ->method('create');

        // Act
        $result = $this->service->onboard($request);

        // Assert
        $this->assertFalse($result->success);
        $this->assertStringContainsString('failed', $result->message);
        $this->assertNotEmpty($result->issues);
    }

    public function testOnboard_WhenSettingsInitializationFails_ReturnsFailureResult(): void
    {
        // Arrange
        $request = new TenantOnboardingRequest(
            tenantCode: 'acme-corp',
            tenantName: 'Acme Corporation',
            domain: 'acme.example.com',
            adminEmail: 'admin@acme.example.com',
            adminPassword: 'securePassword123!',
            plan: 'professional'
        );

        $expectedTenantId = 'tenant-uuid-123';

        $this->tenantCreator
            ->expects($this->once())
            ->method('create')
            ->willReturn($expectedTenantId);

        $this->settingsInitializer
            ->expects($this->once())
            ->method('initialize')
            ->willThrowException(new \RuntimeException('Failed to initialize settings'));

        $this->featureConfigurator
            ->expects($this->never())
            ->method('configure');

        // Act
        $result = $this->service->onboard($request);

        // Assert
        $this->assertFalse($result->success);
        $this->assertStringContainsString('failed', $result->message);
    }

    public function testOnboard_WhenFeatureConfigurationFails_ReturnsFailureResult(): void
    {
        // Arrange
        $request = new TenantOnboardingRequest(
            tenantCode: 'acme-corp',
            tenantName: 'Acme Corporation',
            domain: 'acme.example.com',
            adminEmail: 'admin@acme.example.com',
            adminPassword: 'securePassword123!',
            plan: 'professional'
        );

        $expectedTenantId = 'tenant-uuid-123';

        $this->tenantCreator
            ->expects($this->once())
            ->method('create')
            ->willReturn($expectedTenantId);

        $this->settingsInitializer
            ->expects($this->once())
            ->method('initialize');

        $this->featureConfigurator
            ->expects($this->once())
            ->method('configure')
            ->willThrowException(new \RuntimeException('Invalid feature configuration'));

        // Act
        $result = $this->service->onboard($request);

        // Assert
        $this->assertFalse($result->success);
    }

    public function testOnboard_WhenAdminCreationFails_ReturnsFailureResult(): void
    {
        // Arrange
        $request = new TenantOnboardingRequest(
            tenantCode: 'acme-corp',
            tenantName: 'Acme Corporation',
            domain: 'acme.example.com',
            adminEmail: 'admin@acme.example.com',
            adminPassword: 'weak',
            plan: 'professional'
        );

        $expectedTenantId = 'tenant-uuid-123';
        $expectedCompanyId = 'company-uuid-456';

        $this->tenantCreator
            ->expects($this->once())
            ->method('create')
            ->willReturn($expectedTenantId);

        $this->settingsInitializer
            ->expects($this->once())
            ->method('initialize');

        $this->featureConfigurator
            ->expects($this->once())
            ->method('configure');

        $this->companyCreator
            ->expects($this->once())
            ->method('createDefaultStructure')
            ->willReturn($expectedCompanyId);

        $this->adminCreator
            ->expects($this->once())
            ->method('create')
            ->willThrowException(new \RuntimeException('Password does not meet requirements'));

        // Act
        $result = $this->service->onboard($request);

        // Assert
        $this->assertFalse($result->success);
    }

    // =========================================================================
    // Tests for createTenant()
    // =========================================================================

    public function testCreateTenant_ReturnsTenantId(): void
    {
        // Arrange
        $request = new TenantOnboardingRequest(
            tenantCode: 'test-tenant',
            tenantName: 'Test Tenant',
            domain: 'test.example.com',
            adminEmail: 'admin@test.example.com',
            adminPassword: 'password123',
            plan: 'starter'
        );

        $expectedTenantId = 'tenant-new-123';

        $this->tenantCreator
            ->expects($this->once())
            ->method('create')
            ->with('test-tenant', 'Test Tenant', 'test.example.com')
            ->willReturn($expectedTenantId);

        // Act
        $result = $this->service->createTenant($request);

        // Assert
        $this->assertSame($expectedTenantId, $result);
    }

    // =========================================================================
    // Tests for initializeSettings()
    // =========================================================================

    public function testInitializeSettings_CallsInitializerWithDefaults(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new TenantOnboardingRequest(
            tenantCode: 'test',
            tenantName: 'Test',
            domain: 'test.com',
            adminEmail: 'admin@test.com',
            adminPassword: 'pass',
            plan: 'professional',
            currency: 'EUR',
            timezone: 'Europe/London'
        );

        $expectedSettings = [
            'currency' => 'EUR',
            'timezone' => 'Europe/London',
            'language' => 'en',
            'date_format' => 'Y-m-d',
            'fiscal_year_start' => '01-01',
        ];

        $this->settingsInitializer
            ->expects($this->once())
            ->method('initialize')
            ->with($tenantId, $expectedSettings);

        // Act
        $this->service->initializeSettings($tenantId, $request);
    }

    public function testInitializeSettings_UsesNullDefaults_WhenNotProvided(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new TenantOnboardingRequest(
            tenantCode: 'test',
            tenantName: 'Test',
            domain: 'test.com',
            adminEmail: 'admin@test.com',
            adminPassword: 'pass',
            plan: 'starter'
        );

        $expectedSettings = [
            'currency' => 'USD',
            'timezone' => 'UTC',
            'language' => 'en',
            'date_format' => 'Y-m-d',
            'fiscal_year_start' => '01-01',
        ];

        $this->settingsInitializer
            ->expects($this->once())
            ->method('initialize')
            ->with($tenantId, $expectedSettings);

        // Act
        $this->service->initializeSettings($tenantId, $request);
    }

    // =========================================================================
    // Tests for configureFeatures()
    // =========================================================================

    public function testConfigureFeatures_WithStarterPlan_ConfiguresStarterFeatures(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new TenantOnboardingRequest(
            tenantCode: 'test',
            tenantName: 'Test',
            domain: 'test.com',
            adminEmail: 'admin@test.com',
            adminPassword: 'pass',
            plan: 'starter'
        );

        $expectedFeatures = [
            'finance' => true,
            'hr' => true,
            'sales' => false,
            'procurement' => false,
            'inventory' => false,
            'crm' => false,
            'advanced_reporting' => false,
            'api_access' => false,
        ];

        $this->featureConfigurator
            ->expects($this->once())
            ->method('configure')
            ->with($tenantId, $expectedFeatures);

        // Act
        $this->service->configureFeatures($tenantId, $request);
    }

    public function testConfigureFeatures_WithProfessionalPlan_ConfiguresProfessionalFeatures(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new TenantOnboardingRequest(
            tenantCode: 'test',
            tenantName: 'Test',
            domain: 'test.com',
            adminEmail: 'admin@test.com',
            adminPassword: 'pass',
            plan: 'professional'
        );

        $expectedFeatures = [
            'finance' => true,
            'hr' => true,
            'sales' => true,
            'procurement' => true,
            'inventory' => true,
            'crm' => false,
            'advanced_reporting' => true,
            'api_access' => true,
        ];

        $this->featureConfigurator
            ->expects($this->once())
            ->method('configure')
            ->with($tenantId, $expectedFeatures);

        // Act
        $this->service->configureFeatures($tenantId, $request);
    }

    public function testConfigureFeatures_WithEnterprisePlan_ConfiguresEnterpriseFeatures(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new TenantOnboardingRequest(
            tenantCode: 'test',
            tenantName: 'Test',
            domain: 'test.com',
            adminEmail: 'admin@test.com',
            adminPassword: 'pass',
            plan: 'enterprise'
        );

        $expectedFeatures = [
            'finance' => true,
            'hr' => true,
            'sales' => true,
            'procurement' => true,
            'inventory' => true,
            'crm' => true,
            'advanced_reporting' => true,
            'api_access' => true,
            'custom_branding' => true,
            'multi_entity' => true,
        ];

        $this->featureConfigurator
            ->expects($this->once())
            ->method('configure')
            ->with($tenantId, $expectedFeatures);

        // Act
        $this->service->configureFeatures($tenantId, $request);
    }

    public function testConfigureFeatures_WithUnknownPlan_ConfiguresEmptyFeatures(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new TenantOnboardingRequest(
            tenantCode: 'test',
            tenantName: 'Test',
            domain: 'test.com',
            adminEmail: 'admin@test.com',
            adminPassword: 'pass',
            plan: 'unknown-plan'
        );

        $expectedFeatures = [];

        $this->featureConfigurator
            ->expects($this->once())
            ->method('configure')
            ->with($tenantId, $expectedFeatures);

        // Act
        $this->service->configureFeatures($tenantId, $request);
    }

    // =========================================================================
    // Tests for createCompanyStructure()
    // =========================================================================

    public function testCreateCompanyStructure_ReturnsCompanyId(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new TenantOnboardingRequest(
            tenantCode: 'test',
            tenantName: 'Test Company',
            domain: 'test.com',
            adminEmail: 'admin@test.com',
            adminPassword: 'pass',
            plan: 'professional'
        );

        $expectedCompanyId = 'company-123';

        $this->companyCreator
            ->expects($this->once())
            ->method('createDefaultStructure')
            ->with(
                tenantId: $tenantId,
                companyName: 'Test Company Default Company'
            )
            ->willReturn($expectedCompanyId);

        // Act
        $result = $this->service->createCompanyStructure($tenantId, $request);

        // Assert
        $this->assertSame($expectedCompanyId, $result);
    }

    // =========================================================================
    // Tests for createAdminUser()
    // =========================================================================

    public function testCreateAdminUser_ReturnsAdminUserId(): void
    {
        // Arrange
        $tenantId = 'tenant-123';
        $request = new TenantOnboardingRequest(
            tenantCode: 'test',
            tenantName: 'Test',
            domain: 'test.com',
            adminEmail: 'admin@test.com',
            adminPassword: 'securePassword123!',
            plan: 'professional'
        );

        $expectedAdminUserId = 'user-admin-123';

        $this->adminCreator
            ->expects($this->once())
            ->method('create')
            ->with(
                tenantId: $tenantId,
                email: 'admin@test.com',
                password: 'securePassword123!',
                isAdmin: true
            )
            ->willReturn($expectedAdminUserId);

        // Act
        $result = $this->service->createAdminUser($tenantId, $request);

        // Assert
        $this->assertSame($expectedAdminUserId, $result);
    }

    // =========================================================================
    // Edge Case Tests
    // =========================================================================

    public function testOnboard_WithMinimalRequest_Succeeds(): void
    {
        // Arrange - minimal required fields
        $request = new TenantOnboardingRequest(
            tenantCode: 'test',
            tenantName: 'Test',
            domain: 'test.com',
            adminEmail: 'admin@test.com',
            adminPassword: 'password123',
            plan: 'starter'
        );

        $expectedTenantId = 'tenant-123';
        $expectedCompanyId = 'company-123';
        $expectedAdminUserId = 'user-123';

        $this->tenantCreator->method('create')->willReturn($expectedTenantId);
        $this->companyCreator->method('createDefaultStructure')->willReturn($expectedCompanyId);
        $this->adminCreator->method('create')->willReturn($expectedAdminUserId);

        // Act
        $result = $this->service->onboard($request);

        // Assert
        $this->assertTrue($result->success);
    }

    public function testOnboard_AuditLogsCorrectEvent(): void
    {
        // Arrange
        $request = new TenantOnboardingRequest(
            tenantCode: 'test',
            tenantName: 'Test',
            domain: 'test.com',
            adminEmail: 'admin@test.com',
            adminPassword: 'pass',
            plan: 'starter'
        );

        $expectedTenantId = 'tenant-123';
        $expectedCompanyId = 'company-123';
        $expectedAdminUserId = 'user-123';

        $this->tenantCreator->method('create')->willReturn($expectedTenantId);
        $this->companyCreator->method('createDefaultStructure')->willReturn($expectedCompanyId);
        $this->adminCreator->method('create')->willReturn($expectedAdminUserId);

        $capturedLog = null;
        $this->auditLogger->method('log')->willReturnCallback(function($event, $tenantId, $data) use (&$capturedLog) {
            $capturedLog = ['event' => $event, 'tenantId' => $tenantId, 'data' => $data];
        });

        // Act
        $result = $this->service->onboard($request);

        // Assert
        $this->assertEquals('tenant.onboarded', $capturedLog['event']);
        $this->assertEquals($expectedTenantId, $capturedLog['tenantId']);
        $this->assertEquals('test', $capturedLog['data']['tenant_code']);
        $this->assertEquals('test.com', $capturedLog['data']['domain']);
        $this->assertEquals('starter', $capturedLog['data']['plan']);
        $this->assertEquals($expectedAdminUserId, $capturedLog['data']['admin_user_id']);
        $this->assertEquals($expectedCompanyId, $capturedLog['data']['company_id']);
    }
}
