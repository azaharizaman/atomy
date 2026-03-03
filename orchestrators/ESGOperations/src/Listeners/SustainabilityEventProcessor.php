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
     * @param mixed $event The event object or array payload
     */
    public function handle(mixed $event): void
    {
        try {
            $tenantId = $this->extractField($event, 'tenantId') ?: $this->extractField($event, 'tenant_id');
            $eventId = $this->extractField($event, 'eventId') ?: $this->extractField($event, 'event_id');

            if (!$tenantId || !$eventId) {
                throw new \InvalidArgumentException('Sustainability event missing required tenantId or eventId');
            }

            $this->logger->info('Sustainability event received for processing', [
                'tenant_id' => $tenantId,
                'event_id' => $eventId,
            ]);

            $this->coordinator->processEvent($tenantId, $eventId);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to process sustainability event', [
                'error' => $e->getMessage(),
                'event' => is_object($event) ? get_class($event) : gettype($event),
            ]);
            throw $e;
        }
    }

    /**
     * Helper to extract a field from mixed event.
     */
    private function extractField(mixed $event, string $field): ?string
    {
        $value = null;

        if (is_array($event)) {
            $value = $event[$field] ?? null;
        } elseif (is_object($event)) {
            $method = 'get' . ucfirst($field);
            if (method_exists($event, $method)) {
                $value = $event->$method();
            } elseif (property_exists($event, $field)) {
                $value = $event->$field;
            }
        }

        if ($value === null) {
            return null;
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            return (string)$value;
        }

        return null;
    }
}
