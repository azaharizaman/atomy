<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Contracts;

/**
 * Analytics Provider Interface
 * 
 * Provides access to analytics services for CRM operations.
 * This interface abstracts analytics integration.
 * 
 * @package Nexus\CRMOperations\Contracts
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
interface AnalyticsProviderInterface
{
    /**
     * Track an event
     * 
     * @param string $eventName Event name
     * @param array<string, mixed> $properties Event properties
     */
    public function track(string $eventName, array $properties = []): void;

    /**
     * Track conversion event
     * 
     * @param string $type Conversion type (lead, opportunity, etc.)
     * @param string $entityId Entity ID
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function trackConversion(
        string $type,
        string $entityId,
        array $metadata = []
    ): void;

    /**
     * Get pipeline metrics
     * 
     * @return array<string, mixed>
     */
    public function getPipelineMetrics(string $tenantId): array;

    /**
     * Get conversion rate metrics
     * 
     * @return array<string, mixed>
     */
    public function getConversionRates(
        string $tenantId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array;

    /**
     * Get lead source performance
     * 
     * @return array<string, mixed>
     */
    public function getLeadSourcePerformance(string $tenantId): array;

    /**
     * Get sales performance metrics
     * 
     * @return array<string, mixed>
     */
    public function getSalesPerformance(
        string $tenantId,
        ?string $userId = null
    ): array;

    /**
     * Get forecast accuracy
     * 
     * @return array<string, mixed>
     */
    public function getForecastAccuracy(string $tenantId): array;

    /**
     * Generate report
     * 
     * @param string $reportType Report type identifier
     * @param array<string, mixed> $parameters Report parameters
     * @return array<string, mixed>
     */
    public function generateReport(
        string $reportType,
        array $parameters = []
    ): array;

    /**
     * Export data for external analysis
     * 
     * @param string $format Export format (csv, json, xlsx)
     * @param array<string, mixed> $filters Data filters
     * @return string Exported data or download URL
     */
    public function exportData(
        string $format,
        array $filters = []
    ): string;
}
