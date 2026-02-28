<?php

declare(strict_types=1);

namespace App\Controller;

use App\ApiResource\Tenant as TenantResource;
use Nexus\TenantOperations\Contracts\TenantLifecycleCoordinatorInterface;
use Nexus\TenantOperations\DTOs\TenantSuspendRequest;
use Nexus\TenantOperations\DTOs\TenantActivateRequest;
use Nexus\TenantOperations\DTOs\TenantArchiveRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for tenant lifecycle actions.
 */
#[AsController]
final class TenantLifecycleController extends AbstractController
{
    public function __construct(
        private readonly TenantLifecycleCoordinatorInterface $lifecycleCoordinator
    ) {}

    public function suspend(string $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_TENANT_ADMIN');
        $actorId = $this->getUser()?->getUserIdentifier() ?? 'system';

        $suspendRequest = new TenantSuspendRequest(
            tenantId: $id,
            suspendedBy: $actorId,
            reason: 'Suspended via API'
        );

        $result = $this->lifecycleCoordinator->suspend($suspendRequest);

        if (!$result->isSuccess()) {
            return $this->json(['error' => $result->getMessage()], 400);
        }

        return $this->json(['message' => 'Tenant suspended successfully']);
    }

    public function activate(string $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');
        $actorId = $this->getUser()?->getUserIdentifier() ?? 'system';

        $activateRequest = new TenantActivateRequest(
            tenantId: $id,
            activatedBy: $actorId
        );

        $result = $this->lifecycleCoordinator->activate($activateRequest);

        if (!$result->isSuccess()) {
            return $this->json(['error' => $result->getMessage()], 400);
        }

        return $this->json(['message' => 'Tenant activated successfully']);
    }

    public function archive(string $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');
        $actorId = $this->getUser()?->getUserIdentifier() ?? 'system';

        $archiveRequest = new TenantArchiveRequest(
            tenantId: $id,
            archivedBy: $actorId,
            reason: 'Archived via API'
        );

        $result = $this->lifecycleCoordinator->archive($archiveRequest);

        if (!$result->isSuccess()) {
            return $this->json(['error' => $result->getMessage()], 400);
        }

        return $this->json(['message' => 'Tenant archived successfully']);
    }
}
