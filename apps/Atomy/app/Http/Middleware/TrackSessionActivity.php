<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Nexus\Identity\Contracts\SessionManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Response;

/**
 * Track session activity middleware
 * 
 * Updates last_activity_at timestamp for authenticated requests
 */
final readonly class TrackSessionActivity
{
    public function __construct(
        private SessionManagerInterface $sessionManager,
        private LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * Handle an incoming request
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Extract session ID from authenticated user's session
        // This requires the authentication middleware to set the session_id attribute
        $sessionId = $request->attributes->get('session_id');
        
        if (empty($sessionId)) {
            return $response;
        }

        try {
            // Update activity timestamp
            $this->sessionManager->updateActivity($sessionId);
        } catch (\Exception $e) {
            // Log error but don't fail the request
            $this->logger->warning('Failed to update session activity', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }

        return $response;
    }
}
