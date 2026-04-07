<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Coordinators;

use Nexus\TenantOperations\Contracts\TenantCompanyOnboardingCoordinatorInterface;
use Nexus\TenantOperations\Contracts\TenantCompanyOnboardingServiceInterface;
use Nexus\TenantOperations\DTOs\TenantCompanyOnboardingRequest;
use Nexus\TenantOperations\DTOs\TenantCompanyOnboardingResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Traffic-cop coordinator for the alpha company onboarding flow.
 */
final readonly class TenantCompanyOnboardingCoordinator implements TenantCompanyOnboardingCoordinatorInterface
{
    public function __construct(
        private TenantCompanyOnboardingServiceInterface $onboardingService,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function onboard(TenantCompanyOnboardingRequest $request): TenantCompanyOnboardingResult
    {
        $this->logger->info('Processing company onboarding', [
            'tenant_code' => $request->tenantCode,
            'company_name' => $request->companyName,
            'owner_email' => hash('sha256', strtolower(trim($request->ownerEmail))),
        ]);

        return $this->onboardingService->onboard($request);
    }
}
