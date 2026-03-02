<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Nexus\Identity\Contracts\PolicyEvaluatorInterface;
use Nexus\TenantOperations\Contracts\TenantImpersonationCoordinatorInterface;
use Nexus\TenantOperations\DTOs\ImpersonationStartRequest;
use Nexus\TenantOperations\DTOs\ImpersonationEndRequest;
use Nexus\TenantOperations\Services\ImpersonationSessionManagerInterface;
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
        private readonly TenantImpersonationCoordinatorInterface $impersonationCoordinator,
        private readonly PolicyEvaluatorInterface $policyEvaluator,
        private readonly ImpersonationSessionManagerInterface $sessionManager
    ) {}

    public function start(string $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        if (!$this->policyEvaluator->evaluate($user, 'tenant.impersonate.start', $id)) {
            return $this->json(['error' => 'Access denied'], 403);
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
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        if (!$this->policyEvaluator->evaluate($user, 'tenant.impersonate.stop', $id)) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $sessionId = $request->headers->get('X-Impersonation-Session-ID');
        if (!$sessionId) {
            return $this->json(['error' => 'Session ID required'], 400);
        }

        // Validate session belongs to target tenant
        $session = $this->sessionManager->findSessionById($sessionId);
        if ($session && $session['target_tenant_id'] !== $id) {
            return $this->json(['error' => 'Session does not belong to this tenant'], 403);
        }

        $endRequest = new ImpersonationEndRequest(
            adminUserId: $user->getUserIdentifier(),
            sessionId: $sessionId,
            reason: $request->get('reason', 'Session ended')
        );

        $result = $this->impersonationCoordinator->endImpersonation($endRequest);

        if (!$result->isSuccess()) {
            return $this->json(['error' => $result->getMessage()], 400);
        }

        return $this->json(['message' => 'Impersonation ended successfully']);
    }
}
