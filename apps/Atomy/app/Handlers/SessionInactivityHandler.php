<?php

declare(strict_types=1);

namespace App\Handlers;

use Nexus\Identity\Contracts\SessionManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Session inactivity cleanup handler
 * 
 * Handles scheduled cleanup of inactive sessions
 */
final readonly class SessionInactivityHandler
{
    public function __construct(
        private SessionManagerInterface $sessionManager,
        private LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * Execute inactive session cleanup
     * 
     * @param int $inactivityThresholdDays Days of inactivity before cleanup (default: 7)
     * @return array{deleted_sessions: int, threshold_days: int}
     */
    public function handle(int $inactivityThresholdDays = 7): array
    {
        $this->logger->info('Starting inactive session cleanup', [
            'threshold_days' => $inactivityThresholdDays,
        ]);

        $deletedCount = $this->sessionManager->cleanupInactiveSessions($inactivityThresholdDays);

        $this->logger->info('Inactive session cleanup completed', [
            'deleted_sessions' => $deletedCount,
            'threshold_days' => $inactivityThresholdDays,
        ]);

        return [
            'deleted_sessions' => $deletedCount,
            'threshold_days' => $inactivityThresholdDays,
        ];
    }
}
