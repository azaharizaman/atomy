<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\JwtServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Nexus\IdentityOperations\Services\SessionValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class JwtAuthenticate
{
    public function __construct(
        private JwtServiceInterface $jwt,
        private SessionValidatorInterface $sessionValidator,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');
        if (!str_starts_with($header, 'Bearer ')) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $token = substr($header, 7);

        try {
            $payload = $this->jwt->decode($token);
        } catch (\Throwable) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }

        if ($payload->type !== 'access') {
            return response()->json(['error' => 'Invalid token type'], 401);
        }

        if (is_string($payload->sid) && $payload->sid !== '' && ! $this->sessionValidator->isValid($payload->sid)) {
            return response()->json(['error' => 'Session revoked'], 401);
        }

        $request->attributes->set('auth_user_id', $payload->sub);
        $request->attributes->set('auth_tenant_id', $payload->tenant_id);
        $request->attributes->set('auth_session_id', $payload->sid);

        return $next($request);
    }
}
