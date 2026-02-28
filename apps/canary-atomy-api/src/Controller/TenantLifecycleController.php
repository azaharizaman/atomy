<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Nexus\Identity\Contracts\PolicyEvaluatorInterface;
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
        private readonly TenantLifecycleCoordinatorInterface $lifecycleCoordinator,
        private readonly PolicyEvaluatorInterface $policyEvaluator
    ) {}

    public function suspend(string $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        if (!$this->policyEvaluator->evaluate($user, 'tenant.suspend', $id)) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $suspendRequest = new TenantSuspendRequest(
            tenantId: $id,
            suspendedBy: $user->getUserIdentifier(),
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
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        if (!$this->policyEvaluator->evaluate($user, 'tenant.activate', $id)) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $activateRequest = new TenantActivateRequest(
            tenantId: $id,
            activatedBy: $user->getUserIdentifier()
        );

        $result = $this->lifecycleCoordinator->activate($activateRequest);

        if (!$result->isSuccess()) {
            return $this->json(['error' => $result->getMessage()], 400);
        }

        return $this->json(['message' => 'Tenant activated successfully']);
    }

    public function archive(string $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        if (!$this->policyEvaluator->evaluate($user, 'tenant.archive', $id)) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $archiveRequest = new TenantArchiveRequest(
            tenantId: $id,
            archivedBy: $user->getUserIdentifier(),
            reason: 'Archived via API'
        );

        $result = $this->lifecycleCoordinator->archive($archiveRequest);

        if (!$result->isSuccess()) {
            return $this->json(['error' => $result->getMessage()], 400);
        }

        return $this->json(['message' => 'Tenant archived successfully']);
    }
}
