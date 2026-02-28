<?php

declare(strict_types=1);

namespace App\Controller;

use Nexus\IdentityOperations\Contracts\UserAuthenticationCoordinatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for authentication operations.
 */
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserAuthenticationCoordinatorInterface $authCoordinator
    ) {}

    #[Route('/auth/login', name: 'auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $tenantId = $data['tenantId'] ?? '';

        if (empty($email) || empty($password) || empty($tenantId)) {
            return new JsonResponse(['message' => 'Missing credentials or tenant ID'], 400);
        }

        try {
            $userContext = $this->authCoordinator->authenticate($email, $password, $tenantId);

            return new JsonResponse([
                'userId' => $userContext->userId,
                'email' => $userContext->email,
                'tenantId' => $userContext->tenantId,
                'accessToken' => $userContext->accessToken,
                'refreshToken' => $userContext->refreshToken,
                'sessionId' => $userContext->sessionId,
                'roles' => $userContext->roles,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse(['message' => $e->getMessage()], 401);
        }
    }

    #[Route('/auth/refresh', name: 'auth_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $refreshToken = $data['refreshToken'] ?? '';
        $tenantId = $data['tenantId'] ?? '';

        if (empty($refreshToken) || empty($tenantId)) {
            return new JsonResponse(['message' => 'Missing refresh token or tenant ID'], 400);
        }

        try {
            $userContext = $this->authCoordinator->refreshToken($refreshToken, $tenantId);

            return new JsonResponse([
                'accessToken' => $userContext->accessToken,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse(['message' => $e->getMessage()], 401);
        }
    }

    #[Route('/auth/logout', name: 'auth_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? '';
        $sessionId = $data['sessionId'] ?? null;
        $tenantId = $data['tenantId'] ?? '';

        if (empty($userId) || empty($tenantId)) {
            return new JsonResponse(['message' => 'Missing user ID or tenant ID'], 400);
        }

        try {
            $this->authCoordinator->logout($userId, $sessionId, $tenantId);

            return new JsonResponse(['message' => 'Logged out successfully']);
        } catch (\Throwable $e) {
            return new JsonResponse(['message' => $e->getMessage()], 500);
        }
    }
}
