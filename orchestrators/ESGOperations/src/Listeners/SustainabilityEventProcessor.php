<?php

declare(strict_types=1);

namespace Nexus\ESGOperations\Listeners;

use Nexus\ESGOperations\Contracts\ESGOperationsCoordinatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Listener that reacts to raw sustainability events and triggers promotion to ESG metrics.
 */
final readonly class SustainabilityEventProcessor
{
    public function __construct(
        private ESGOperationsCoordinatorInterface $coordinator,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Handle the event.
     * 
     * @param mixed $event The event object (Assuming generic event from stream)
     */
    public function handle(mixed $event): void
    {
        // Extraction of tenantId and eventId from payload
        // $this->coordinator->processEvent($tenantId, $eventId);
    }
}
