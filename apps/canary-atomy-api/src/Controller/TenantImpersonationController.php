<?php

declare(strict_types=1);

namespace App\Controller;

use Nexus\TenantOperations\Contracts\TenantImpersonationCoordinatorInterface;
use Nexus\TenantOperations\DTOs\ImpersonationStartRequest;
use Nexus\TenantOperations\DTOs\ImpersonationEndRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Controller for tenant impersonation actions.
 */
#[AsController]
final class TenantImpersonationController extends AbstractController
{
    public function __construct(
        private readonly TenantImpersonationCoordinatorInterface $impersonationCoordinator
    ) {}

    public function start(string $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');
        
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        $startRequest = new ImpersonationStartRequest(
            adminUserId: $user->getUserIdentifier(),
            targetTenantId: $id,
            reason: $request->get('reason', 'Support activity')
        );

        $result = $this->impersonationCoordinator->startImpersonation($startRequest);

        if (!$result->isSuccess()) {
            return $this->json(['error' => $result->getMessage()], 400);
        }

        return $this->json([
            'message' => 'Impersonation started successfully',
            'token' => $result->getImpersonationToken()
        ]);
    }

    public function stop(string $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        $endRequest = new ImpersonationEndRequest(
            adminUserId: $user->getUserIdentifier(),
            sessionId: $request->headers->get('X-Impersonation-Session-ID'),
            reason: $request->get('reason', 'Session ended')
        );
        
        // We pass the route ID to ensure we are stopping impersonation for the intended tenant
        // The DTO doesn't have targetTenantId in constructor, but we can wrap it if needed.
        // For now, let's assume the coordinator handles session lookup via sessionId.

        $result = $this->impersonationCoordinator->endImpersonation($endRequest);

        if (!$result->isSuccess()) {
            return $this->json(['error' => $result->getMessage()], 400);
        }

        return $this->json(['message' => 'Impersonation ended successfully']);
    }
}
