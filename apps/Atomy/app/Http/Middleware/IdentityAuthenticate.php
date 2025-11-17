<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Nexus\Identity\Contracts\TokenManagerInterface;
use Nexus\Identity\Exceptions\InvalidSessionException;
use Nexus\Identity\Exceptions\InvalidTokenException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identity authentication middleware
 * Replaces Laravel Sanctum
 */
final readonly class IdentityAuthenticate
{
    public function __construct(
        private SessionManagerInterface $sessionManager,
        private TokenManagerInterface $tokenManager
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Check for Bearer token (API)
        if ($token = $request->bearerToken()) {
            return $this->authenticateWithToken($token, $request, $next);
        }

        // Check for session token (Web)
        if ($token = $request->header('X-Session-Token')) {
            return $this->authenticateWithSession($token, $request, $next);
        }

        return response()->json([
            'error' => 'Unauthenticated',
            'message' => 'No authentication credentials provided',
        ], 401);
    }

    private function authenticateWithToken(string $token, Request $request, Closure $next): Response
    {
        try {
            $user = $this->tokenManager->validateToken($token);
            $request->attributes->set('authenticated_user', $user);
            $request->attributes->set('auth_type', 'token');
            $request->attributes->set('token_scopes', $this->tokenManager->getTokenScopes($token));

            return $next($request);
        } catch (InvalidTokenException $e) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Invalid or expired API token',
            ], 401);
        }
    }

    private function authenticateWithSession(string $token, Request $request, Closure $next): Response
    {
        try {
            $user = $this->sessionManager->validateSession($token);
            $request->attributes->set('authenticated_user', $user);
            $request->attributes->set('auth_type', 'session');

            return $next($request);
        } catch (InvalidSessionException $e) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Invalid or expired session',
            ], 401);
        }
    }
}
