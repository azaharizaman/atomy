<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Tests\Mocks;

use Nexus\CRMOperations\Contracts\AnalyticsProviderInterface;

/**
 * Mock implementation of AnalyticsProviderInterface for testing
 * 
 * @package Nexus\CRMOperations\Tests\Mocks
 */
final class MockAnalyticsProvider implements AnalyticsProviderInterface
{
    private array $events = [];
    private array $conversions = [];

    public function track(string $eventName, array $properties = []): void
    {
        $this->events[] = [
            'event' => $eventName,
            'properties' => $properties,
            'timestamp' => time(),
        ];
    }

    public function trackConversion(
        string $type,
        string $entityId,
        array $metadata = []
    ): void {
        $this->conversions[] = [
            'type' => $type,
            'entity_id' => $entityId,
            'metadata' => $metadata,
            'timestamp' => time(),
        ];
    }

    public function getPipelineMetrics(string $tenantId): array
    {
        return [
            'total_opportunities' => 0,
            'total_value' => 0,
            'weighted_value' => 0,
            'conversion_rate' => 0.0,
            'average_deal_size' => 0,
        ];
    }

    public function getConversionRates(
        string $tenantId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        return [
            'lead_to_opportunity' => 0.0,
            'opportunity_to_close' => 0.0,
            'overall' => 0.0,
        ];
    }

    public function getLeadSourcePerformance(string $tenantId): array
    {
        return [];
    }

    public function getSalesPerformance(string $tenantId, ?string $userId = null): array
    {
        return [
            'deals_closed' => 0,
            'revenue' => 0,
            'average_deal_size' => 0,
            'win_rate' => 0.0,
        ];
    }

    public function getForecastAccuracy(string $tenantId): array
    {
        return [
            'accuracy' => 0.0,
            'variance' => 0.0,
            'trend' => 'stable',
        ];
    }

    public function generateReport(string $reportType, array $parameters = []): array
    {
        return [
            'type' => $reportType,
            'generated_at' => (new \DateTimeImmutable())->format('c'),
            'data' => [],
        ];
    }

    public function exportData(string $format, array $filters = []): string
    {
        return "/exports/test.{$format}";
    }

    // Helper methods for testing
    public function getEventCount(): int
    {
        return count($this->events);
    }

    public function getConversionCount(): int
    {
        return count($this->conversions);
    }
}
