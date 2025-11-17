<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Nexus\Identity\Contracts\TokenManagerInterface;
use Nexus\Identity\Contracts\UserAuthenticatorInterface;
use Nexus\Identity\Contracts\UserManagerInterface;
use Nexus\Identity\Exceptions\InvalidCredentialsException;
use Nexus\Identity\Exceptions\AccountLockedException;
use Nexus\Identity\ValueObjects\Credentials;

final readonly class AuthenticationController
{
    public function __construct(
        private UserAuthenticatorInterface $authenticator,
        private UserManagerInterface $userManager,
        private SessionManagerInterface $sessionManager,
        private TokenManagerInterface $tokenManager
    ) {
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $credentials = new Credentials(
                $validated['email'],
                $validated['password']
            );

            $user = $this->authenticator->authenticate($credentials);

            // Create session token
            $sessionToken = $this->sessionManager->createSession(
                $user->getId(),
                [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            return response()->json([
                'message' => 'Login successful',
                'session_token' => $sessionToken->token,
                'expires_at' => $sessionToken->expiresAt->format('c'),
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                ],
            ]);
        } catch (InvalidCredentialsException $e) {
            return response()->json([
                'error' => 'Invalid credentials',
                'message' => $e->getMessage(),
            ], 401);
        } catch (AccountLockedException $e) {
            return response()->json([
                'error' => 'Account locked',
                'message' => $e->getMessage(),
            ], 423);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->header('X-Session-Token') ?? $request->bearerToken();

        if ($token) {
            $this->sessionManager->revokeSession($token);
        }

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    public function createToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'scopes' => 'array',
            'scopes.*' => 'string',
            'expires_at' => 'nullable|date',
        ]);

        $user = $request->attributes->get('authenticated_user');

        $apiToken = $this->tokenManager->generateToken(
            $user->getId(),
            $validated['name'],
            $validated['scopes'] ?? [],
            isset($validated['expires_at']) ? new \DateTime($validated['expires_at']) : null
        );

        return response()->json([
            'message' => 'Token created successfully',
            'token' => $apiToken->token,
            'token_id' => $apiToken->id,
            'name' => $apiToken->name,
            'scopes' => $apiToken->scopes,
            'expires_at' => $apiToken->expiresAt?->format('c'),
        ], 201);
    }

    public function revokeToken(Request $request, string $tokenId): JsonResponse
    {
        $this->tokenManager->revokeToken($tokenId);

        return response()->json([
            'message' => 'Token revoked successfully',
        ]);
    }

    public function listTokens(Request $request): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $tokens = $this->tokenManager->getUserTokens($user->getId());

        return response()->json([
            'tokens' => $tokens,
        ]);
    }

    public function listSessions(Request $request): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $sessions = $this->sessionManager->getActiveSessions($user->getId());

        return response()->json([
            'sessions' => $sessions,
        ]);
    }

    public function revokeAllSessions(Request $request): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $currentToken = $request->header('X-Session-Token');

        if ($currentToken) {
            $this->sessionManager->revokeOtherSessions($user->getId(), $currentToken);
        } else {
            $this->sessionManager->revokeAllSessions($user->getId());
        }

        return response()->json([
            'message' => 'Sessions revoked successfully',
        ]);
    }
}
