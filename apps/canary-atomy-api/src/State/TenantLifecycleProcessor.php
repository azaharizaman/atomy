<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Tenant as TenantResource;
use Nexus\TenantOperations\Contracts\TenantLifecycleCoordinatorInterface;
use Nexus\TenantOperations\DTOs\TenantDeleteRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Processor for tenant lifecycle operations.
 *
 * Currently handles DELETE operation.
 */
final class TenantLifecycleProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly TenantLifecycleCoordinatorInterface $lifecycleCoordinator
    ) {}

    /**
     * @param TenantResource $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $id = $uriVariables['id'] ?? null;
        if (!$id) {
            return;
        }

        if ($operation->getMethod() === 'DELETE') {
            $request = new TenantDeleteRequest(
                tenantId: $id,
                force: false,
                reason: 'Requested via API'
            );

            $result = $this->lifecycleCoordinator->delete($request);

            if (!$result->isSuccess()) {
                throw new BadRequestHttpException($result->getMessage() ?: 'Delete failed');
            }
        }
    }
}
