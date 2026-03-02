<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\FeatureFlag as FeatureFlagResource;
use App\Service\TenantContext;
use Nexus\SettingsManagement\Contracts\FeatureFlagCoordinatorInterface;
use Nexus\SettingsManagement\DTOs\FeatureFlags\FeatureFlagUpdateRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Processor for FeatureFlag resource.
 */
final class FeatureFlagProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly FeatureFlagCoordinatorInterface $flagCoordinator,
        private readonly TenantContext $tenantContext
    ) {}

    /**
     * @param FeatureFlagResource $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): FeatureFlagResource
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $name = $uriVariables['name'] ?? $data->name;

        if (!$name) {
            throw new BadRequestHttpException('Feature flag name is required');
        }

        $request = new FeatureFlagUpdateRequest(
            name: $name,
            enabled: $data->enabled,
            value: $data->value,
            tenantId: $tenantId
        );

        $result = $this->flagCoordinator->updateFeatureFlag($request);

        if (!$result->isSuccess()) {
            throw new BadRequestHttpException($result->getMessage() ?: 'Update failed');
        }

        return $data;
    }
}
