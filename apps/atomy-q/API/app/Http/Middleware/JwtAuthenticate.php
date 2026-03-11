<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class JwtAuthenticate
{
    public function __construct(
        private JwtService $jwt,
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

        if (($payload->type ?? '') !== 'access') {
            return response()->json(['error' => 'Invalid token type'], 401);
        }

        $request->attributes->set('auth_user_id', $payload->sub);
        $request->attributes->set('auth_tenant_id', $payload->tenant_id);

        return $next($request);
    }
}
