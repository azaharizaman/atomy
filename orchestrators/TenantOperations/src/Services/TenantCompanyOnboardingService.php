<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Services;

use Nexus\TenantOperations\Contracts\AdminCreatorAdapterInterface;
use Nexus\TenantOperations\Contracts\TenantCompanyOnboardingServiceInterface;
use Nexus\TenantOperations\Contracts\TenantCreatorAdapterInterface;
use Nexus\TenantOperations\DTOs\TenantCompanyOnboardingRequest;
use Nexus\TenantOperations\DTOs\TenantCompanyOnboardingResult;
use Nexus\Tenant\Exceptions\DuplicateTenantCodeException;
use Nexus\Tenant\Exceptions\DuplicateTenantDomainException;
use Nexus\Tenant\Exceptions\DuplicateTenantNameException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Minimal alpha onboarding workflow for creating a company and its first owner.
 */
final readonly class TenantCompanyOnboardingService implements TenantCompanyOnboardingServiceInterface
{
    public function __construct(
        private TenantCreatorAdapterInterface $tenantCreator,
        private AdminCreatorAdapterInterface $adminCreator,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function onboard(TenantCompanyOnboardingRequest $request): TenantCompanyOnboardingResult
    {
        try {
            $tenantDomain = $this->deriveTenantDomain($request->tenantCode);
            $tenantId = $this->tenantCreator->create(
                code: $request->tenantCode,
                name: $request->companyName,
                email: $request->ownerEmail,
                domain: $tenantDomain,
                timezone: $request->timezone,
                locale: $request->locale,
                currency: $request->currency,
                metadata: $request->metadata,
            );

            if (trim($tenantId) === '') {
                return TenantCompanyOnboardingResult::failure([
                    [
                        'rule' => 'tenant_creation',
                        'message' => 'Tenant creation failed',
                    ],
                ]);
            }

            $ownerUserId = $this->adminCreator->create(
                tenantId: $tenantId,
                email: $request->ownerEmail,
                password: $request->ownerPassword,
                firstName: $request->getOwnerFirstName(),
                lastName: $request->getOwnerLastName(),
                isAdmin: true,
                locale: $request->locale,
                timezone: $request->timezone,
                metadata: $request->metadata,
            );

            if (trim($ownerUserId) === '') {
                return TenantCompanyOnboardingResult::failure([
                    [
                        'rule' => 'owner_creation',
                        'message' => 'Owner user creation failed',
                    ],
                ]);
            }

            return TenantCompanyOnboardingResult::success(
                tenantId: $tenantId,
                ownerUserId: $ownerUserId,
                bootstrapData: [
                    'tenant_domain' => $tenantDomain,
                    'tenant_code' => $request->tenantCode,
                ],
            );
        } catch (DuplicateTenantCodeException $e) {
            $this->logger->warning('Company onboarding rejected due to duplicate tenant code', [
                'tenant_code' => $request->tenantCode,
                'company_name' => $request->companyName,
                'error' => $e->getMessage(),
            ]);

            return TenantCompanyOnboardingResult::failure([
                [
                    'rule' => 'tenant_code',
                    'message' => $e->getMessage(),
                ],
            ], 'Company onboarding failed');
        } catch (DuplicateTenantNameException $e) {
            $this->logger->warning('Company onboarding rejected due to duplicate tenant name', [
                'tenant_code' => $request->tenantCode,
                'company_name' => $request->companyName,
                'error' => $e->getMessage(),
            ]);

            return TenantCompanyOnboardingResult::failure([
                [
                    'rule' => 'company_name',
                    'message' => $e->getMessage(),
                ],
            ], 'Company onboarding failed');
        } catch (DuplicateTenantDomainException $e) {
            $this->logger->warning('Company onboarding rejected due to duplicate tenant domain', [
                'tenant_code' => $request->tenantCode,
                'company_name' => $request->companyName,
                'error' => $e->getMessage(),
            ]);

            return TenantCompanyOnboardingResult::failure([
                [
                    'rule' => 'tenant_domain',
                    'message' => $e->getMessage(),
                ],
            ], 'Company onboarding failed');
        } catch (\Throwable $e) {
            $this->logger->error('Company onboarding failed', [
                'tenant_code' => $request->tenantCode,
                'company_name' => $request->companyName,
                'error' => $e->getMessage(),
            ]);

            return TenantCompanyOnboardingResult::failure([
                [
                    'rule' => 'onboarding',
                    'message' => 'Company onboarding failed',
                ],
            ]);
        }
    }

    private function deriveTenantDomain(string $tenantCode): string
    {
        $slug = strtolower(trim($tenantCode));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? $slug;
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = 'tenant';
        }

        return $slug . '.local';
    }
}
