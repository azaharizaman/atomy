<?php

declare(strict_types=1);

namespace Nexus\SustainabilityData\Services;

use Nexus\SustainabilityData\Contracts\SustainabilityEventInterface;

/**
 * Service for sampling and aggregating high-frequency sustainability events.
 * 
 * Prevents data overload by collapsing granular readings into periodic summaries.
 */
final readonly class EventSampler
{
    /**
     * Aggregate a collection of events into a single mean value.
     * 
     * @param array<SustainabilityEventInterface> $events
     * @return float
     */
    public function calculateMean(array $events): float
    {
        if (empty($events)) {
            return 0.0;
        }

        $sum = array_reduce($events, fn(float $carry, SustainabilityEventInterface $e) => $carry + $e->getValue(), 0.0);
        return $sum / count($events);
    }

    /**
     * Sum all event values (e.g., for cumulative energy consumption).
     * 
     * @param array<SustainabilityEventInterface> $events
     * @return float
     */
    public function calculateSum(array $events): float
    {
        return array_reduce($events, fn(float $carry, SustainabilityEventInterface $e) => $carry + $e->getValue(), 0.0);
    }

    /**
     * Sample events based on a time interval (e.g., take the first event of every hour).
     * 
     * @param array<SustainabilityEventInterface> $events
     * @param int $intervalSeconds
     * @return array<SustainabilityEventInterface>
     */
    public function sampleByInterval(array $events, int $intervalSeconds): array
    {
        if (empty($events)) {
            return [];
        }

        // Sort by time first
        usort($events, fn(SustainabilityEventInterface $a, SustainabilityEventInterface $b) => $a->getOccurredAt() <=> $b->getOccurredAt());

        $sampled = [];
        $lastTimestamp = null;

        foreach ($events as $event) {
            $timestamp = $event->getOccurredAt()->getTimestamp();
            if ($lastTimestamp === null || ($timestamp - $lastTimestamp) >= $intervalSeconds) {
                $sampled[] = $event;
                $lastTimestamp = $timestamp;
            }
        }

        return $sampled;
    }
}
