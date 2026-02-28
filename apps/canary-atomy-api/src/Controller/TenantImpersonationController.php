<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\TenantContext;
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
        $startRequest = new ImpersonationStartRequest(
            targetTenantId: $id,
            impersonatorUserId: 'superadmin', // Should be from security context
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
        $endRequest = new ImpersonationEndRequest(
            targetTenantId: $id,
            impersonationToken: $request->headers->get('X-Impersonation-Token') ?? ''
        );

        $result = $this->impersonationCoordinator->endImpersonation($endRequest);

        if (!$result->isSuccess()) {
            return $this->json(['error' => $result->getMessage()], 400);
        }

        return $this->json(['message' => 'Impersonation ended successfully']);
    }
}
