<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Nexus\IdentityOperations\Contracts\UserLifecycleCoordinatorInterface;
use Nexus\IdentityOperations\DTOs\UserActivateRequest;
use Nexus\IdentityOperations\DTOs\UserSuspendRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for user lifecycle operations.
 */
#[AsController]
final class UserLifecycleController extends AbstractController
{
    public function __construct(
        private readonly UserLifecycleCoordinatorInterface $lifecycleCoordinator
    ) {}

    #[Route('/users/{id}/suspend', name: 'user_suspend', methods: ['POST'])]
    public function suspend(User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? 'Suspended by admin';
        $performedBy = 'admin'; // In a real app, this would be the current user

        $result = $this->lifecycleCoordinator->suspend(new UserSuspendRequest(
            userId: $user->getId(),
            suspendedBy: $performedBy,
            tenantId: $user->getTenantId() ?? 'GLOBAL',
            reason: $reason,
        ));

        if ($result->success) {
            return new JsonResponse(['message' => 'User suspended successfully']);
        }

        return new JsonResponse(['message' => $result->message], 400);
    }

    #[Route('/users/{id}/activate', name: 'user_activate', methods: ['POST'])]
    public function activate(User $user, Request $request): JsonResponse
    {
        $performedBy = 'admin'; // In a real app, this would be the current user

        $result = $this->lifecycleCoordinator->activate(new UserActivateRequest(
            userId: $user->getId(),
            activatedBy: $performedBy,
            tenantId: $user->getTenantId() ?? 'GLOBAL',
        ));

        if ($result->success) {
            return new JsonResponse(['message' => 'User activated successfully']);
        }

        return new JsonResponse(['message' => $result->message], 400);
    }
}
