<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Tenant as TenantResource;
use App\Entity\User;
use Nexus\Identity\Contracts\PermissionCheckerInterface;
use Nexus\TenantOperations\Contracts\TenantLifecycleCoordinatorInterface;
use Nexus\TenantOperations\DTOs\TenantDeleteRequest;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Processor for tenant lifecycle operations.
 *
 * Currently handles DELETE operation.
 */
final class TenantLifecycleProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly TenantLifecycleCoordinatorInterface $lifecycleCoordinator,
        private readonly Security $security,
        private readonly PermissionCheckerInterface $permissionChecker
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
            $user = $this->security->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedHttpException('Authenticated actor required');
            }

            // Nexus Identity policy check
            if (!$this->permissionChecker->isSuperAdmin($user)) {
                throw new AccessDeniedHttpException('Only Super Admins can delete tenants');
            }

            $request = new TenantDeleteRequest(
                tenantId: $id,
                deletedBy: $user->getUserIdentifier(),
                reason: 'Requested via API',
                exportData: false
            );

            $result = $this->lifecycleCoordinator->delete($request);

            if (!$result->isSuccess()) {
                throw new BadRequestHttpException($result->getMessage() ?: 'Delete failed');
            }
        }
    }
}
