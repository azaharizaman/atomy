<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Tenant as TenantResource;
use Nexus\TenantOperations\Contracts\TenantOnboardingCoordinatorInterface;
use Nexus\TenantOperations\DTOs\TenantOnboardingRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Processor for tenant onboarding.
 *
 * Uses TenantOperations orchestrator to onboard a new tenant.
 */
final class TenantOnboardingProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly TenantOnboardingCoordinatorInterface $onboardingCoordinator
    ) {}

    /**
     * @param TenantResource $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TenantResource
    {
        if (!$data instanceof TenantResource) {
            throw new \InvalidArgumentException('Expected TenantResource');
        }

        $request = new TenantOnboardingRequest(
            tenantCode: $data->code ?? '',
            tenantName: $data->name ?? '',
            domain: $data->domain ?? '',
            adminEmail: $data->adminEmail ?? '',
            adminPassword: $data->adminPassword ?? 'password123',
            plan: $data->plan ?? 'starter',
            metadata: []
        );

        // Validate prerequisites
        $validation = $this->onboardingCoordinator->validatePrerequisites($request);
        if (!$validation->isValid()) {
            throw new BadRequestHttpException(implode(', ', array_map(fn($e) => $e['message'], $validation->getErrors())));
        }

        // Onboard tenant
        $result = $this->onboardingCoordinator->onboard($request);

        if (!$result->isSuccess()) {
            throw new BadRequestHttpException($result->getMessage() ?: 'Onboarding failed');
        }

        $data->id = $result->getTenantId();
        $data->status = 'active';

        return $data;
    }
}
