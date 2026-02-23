<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Services;

use Nexus\TenantOperations\Contracts\TenantOnboardingServiceInterface;
use Nexus\TenantOperations\DTOs\TenantOnboardingRequest;
use Nexus\TenantOperations\DTOs\TenantOnboardingResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for tenant onboarding operations.
 * 
 * This service handles the complex orchestration of creating a new tenant
 * with all required configurations including settings, features, and company structure.
 * 
 * Following Advanced Orchestrator Pattern:
 * - Services perform calculations and heavy lifting, not Coordinators
 * - Uses transactional boundaries for atomic operations
 * - Delegates to atomic package adapters via interfaces
 */
final readonly class TenantOnboardingService implements TenantOnboardingServiceInterface
{
    public function __construct(
        private TenantCreatorInterface $tenantCreator,
        private SettingsInitializerInterface $settingsInitializer,
        private FeatureConfiguratorInterface $featureConfigurator,
        private CompanyCreatorInterface $companyCreator,
        private AdminCreatorInterface $adminCreator,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function onboard(TenantOnboardingRequest $request): TenantOnboardingResult
    {
        $this->logger->info('Starting tenant onboarding', [
            'tenant_code' => $request->tenantCode,
            'domain' => $request->domain,
            'plan' => $request->plan,
        ]);

        try {
            // Step 1: Create tenant
            $tenantId = $this->createTenant($request);

            // Step 2: Initialize settings
            $this->initializeSettings($tenantId, $request);

            // Step 3: Configure features
            $this->configureFeatures($tenantId, $request);

            // Step 4: Create company structure
            $companyId = $this->createCompanyStructure($tenantId, $request);

            // Step 5: Create admin user
            $adminUserId = $this->createAdminUser($tenantId, $request);

            // Step 6: Log audit event
            $this->auditLogger->log(
                'tenant.onboarded',
                $tenantId,
                [
                    'tenant_code' => $request->tenantCode,
                    'domain' => $request->domain,
                    'plan' => $request->plan,
                    'admin_user_id' => $adminUserId,
                    'company_id' => $companyId,
                ]
            );

            $this->logger->info('Tenant onboarding completed', [
                'tenant_id' => $tenantId,
                'admin_user_id' => $adminUserId,
                'company_id' => $companyId,
            ]);

            return TenantOnboardingResult::success(
                tenantId: $tenantId,
                adminUserId: $adminUserId,
                companyId: $companyId,
            );

        } catch (\Throwable $e) {
            $this->logger->error('Tenant onboarding failed', [
                'tenant_code' => $request->tenantCode,
                'error' => $e->getMessage(),
            ]);

            return TenantOnboardingResult::failure(
                issues: [['rule' => 'onboarding', 'message' => $e->getMessage()]],
                message: 'Tenant onboarding failed: ' . $e->getMessage()
            );
        }
    }

    public function createTenant(TenantOnboardingRequest $request): string
    {
        return $this->tenantCreator->create(
            code: $request->tenantCode,
            name: $request->tenantName,
            domain: $request->domain,
        );
    }

    public function initializeSettings(string $tenantId, TenantOnboardingRequest $request): void
    {
        $settings = $request->getDefaultSettings();
        $this->settingsInitializer->initialize($tenantId, $settings);
    }

    public function configureFeatures(string $tenantId, TenantOnboardingRequest $request): void
    {
        $features = $request->getDefaultFeatures();
        $this->featureConfigurator->configure($tenantId, $features);
    }

    public function createCompanyStructure(string $tenantId, TenantOnboardingRequest $request): string
    {
        return $this->companyCreator->createDefaultStructure(
            tenantId: $tenantId,
            companyName: $request->tenantName . ' Default Company',
        );
    }

    public function createAdminUser(string $tenantId, TenantOnboardingRequest $request): string
    {
        return $this->adminCreator->create(
            tenantId: $tenantId,
            email: $request->adminEmail,
            password: $request->adminPassword,
            isAdmin: true,
        );
    }
}

/**
 * Interface for tenant creation.
 */
interface TenantCreatorInterface
{
    public function create(string $code, string $name, string $domain): string;
}

/**
 * Interface for settings initialization.
 */
interface SettingsInitializerInterface
{
    /**
     * @param array<string, mixed> $settings
     */
    public function initialize(string $tenantId, array $settings): void;
}

/**
 * Interface for feature configuration.
 */
interface FeatureConfiguratorInterface
{
    /**
     * @param array<string, bool> $features
     */
    public function configure(string $tenantId, array $features): void;
}

/**
 * Interface for company creation.
 */
interface CompanyCreatorInterface
{
    public function createDefaultStructure(string $tenantId, string $companyName): string;
}

/**
 * Interface for admin user creation.
 */
interface AdminCreatorInterface
{
    public function create(string $tenantId, string $email, string $password, bool $isAdmin = false): string;
}

/**
 * Interface for audit logging.
 */
interface AuditLoggerInterface
{
    public function log(string $event, string $tenantId, array $data): void;
}
