<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Coordinators;

use Nexus\TenantOperations\Contracts\TenantOnboardingCoordinatorInterface;
use Nexus\TenantOperations\DTOs\TenantOnboardingRequest;
use Nexus\TenantOperations\DTOs\TenantOnboardingResult;
use Nexus\TenantOperations\DTOs\ValidationResult;
use Nexus\TenantOperations\Services\TenantOnboardingService;
use Nexus\TenantOperations\Rules\TenantCodeUniqueRule;
use Nexus\TenantOperations\Rules\TenantDomainUniqueRule;
use Nexus\TenantOperations\DataProviders\TenantContextDataProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for tenant onboarding operations.
 * 
 * Following Advanced Orchestrator Pattern:
 * - Traffic cop: directs flow, doesn't do work
 * - Calls DataProvider for context
 * - Calls Rules for validation
 * - Delegates to Services for execution
 */
final readonly class TenantOnboardingCoordinator implements TenantOnboardingCoordinatorInterface
{
    public function __construct(
        private TenantOnboardingService $onboardingService,
        private TenantContextDataProvider $contextDataProvider,
        private TenantCodeUniqueRule $tenantCodeUniqueRule,
        private TenantDomainUniqueRule $tenantDomainUniqueRule,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getName(): string
    {
        return 'TenantOnboardingCoordinator';
    }

    public function hasRequiredData(string $tenantId): bool
    {
        $context = $this->contextDataProvider->getContext($tenantId);
        
        return $context->tenantId !== null 
            && !empty($context->settings) 
            && !empty($context->features);
    }

    public function onboard(TenantOnboardingRequest $request): TenantOnboardingResult
    {
        $this->logger->info('Processing tenant onboarding', [
            'tenant_code' => $request->tenantCode,
            'domain' => $request->domain,
        ]);

        // Validate prerequisites first
        $validationResult = $this->validatePrerequisites($request);

        if (!$validationResult->passed) {
            $this->logger->warning('Tenant onboarding prerequisites validation failed', [
                'tenant_code' => $request->tenantCode,
                'errors' => $validationResult->errors,
            ]);

            return TenantOnboardingResult::failure(
                issues: array_map(fn($e) => ['rule' => $e['rule'], 'message' => $e['message']], $validationResult->errors),
                message: 'Prerequisites validation failed'
            );
        }

        // Delegate to service for onboarding
        return $this->onboardingService->onboard($request);
    }

    public function validatePrerequisites(TenantOnboardingRequest $request): ValidationResult
    {
        $this->logger->debug('Validating onboarding prerequisites', [
            'tenant_code' => $request->tenantCode,
            'domain' => $request->domain,
        ]);

        $errors = [];

        // Validate tenant code uniqueness
        $codeResult = $this->tenantCodeUniqueRule->evaluate($request);
        if (!$codeResult->passed) {
            $errors = array_merge($errors, $codeResult->errors);
        }

        // Validate domain uniqueness
        $domainResult = $this->tenantDomainUniqueRule->evaluate($request);
        if (!$domainResult->passed) {
            $errors = array_merge($errors, $domainResult->errors);
        }

        if (!empty($errors)) {
            return ValidationResult::failed($errors);
        }

        return ValidationResult::passed();
    }
}
