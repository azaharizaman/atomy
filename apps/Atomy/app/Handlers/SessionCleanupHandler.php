<?php

declare(strict_types=1);

namespace App\Handlers;

use Nexus\Identity\Contracts\SessionManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Session cleanup handler
 * 
 * Handles scheduled cleanup of expired sessions
 */
final readonly class SessionCleanupHandler
{
    public function __construct(
        private SessionManagerInterface $sessionManager,
        private LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * Execute session cleanup
     * 
     * @return array{deleted_sessions: int}
     */
    public function handle(): array
    {
        $this->logger->info('Starting expired session cleanup');

        $deletedCount = $this->sessionManager->cleanupExpiredSessions();

        $this->logger->info('Expired session cleanup completed', [
            'deleted_sessions' => $deletedCount,
        ]);

        return [
            'deleted_sessions' => $deletedCount,
        ];
    }
}
